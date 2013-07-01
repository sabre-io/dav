<?php

namespace Sabre\DAV;

use
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface;

/**
 * The core plugin provides all the basic features for a WebDAV server.
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class CorePlugin extends ServerPlugin {

    /**
     * Reference to server object.
     *
     * @var Server
     */
    protected $server;

    /**
     * Sets up the plugin
     *
     * @param Server $server
     * @return void
     */
    public function initialize(Server $server) {

        $this->server = $server;
        $server->on('method:GET', [$this, 'httpGet']);

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName() {

        return 'core';

    }

    /**
     * This is the default implementation for the GET method.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();
        $node = $this->server->tree->getNodeForPath($path,0);

        if (!$this->server->checkPreconditions(true)) return false;
        if (!$node instanceof IFile) return;

        $body = $node->get();

        // Converting string into stream, if needed.
        if (is_string($body)) {
            $stream = fopen('php://temp','r+');
            fwrite($stream,$body);
            rewind($stream);
            $body = $stream;
        }

        /*
         * TODO: getetag, getlastmodified, getsize should also be used using
         * this method
         */
        $httpHeaders = $this->server->getHTTPHeaders($path);

        /* ContentType needs to get a default, because many webservers will otherwise
         * default to text/html, and we don't want this for security reasons.
         */
        if (!isset($httpHeaders['Content-Type'])) {
            $httpHeaders['Content-Type'] = 'application/octet-stream';
        }


        if (isset($httpHeaders['Content-Length'])) {

            $nodeSize = $httpHeaders['Content-Length'];

            // Need to unset Content-Length, because we'll handle that during figuring out the range
            unset($httpHeaders['Content-Length']);

        } else {
            $nodeSize = null;
        }

        $response->setHeaders($httpHeaders);

        $range = $this->server->getHTTPRange();
        $ifRange = $request->getHeader('If-Range');
        $ignoreRangeHeader = false;

        // If ifRange is set, and range is specified, we first need to check
        // the precondition.
        if ($nodeSize && $range && $ifRange) {

            // if IfRange is parsable as a date we'll treat it as a DateTime
            // otherwise, we must treat it as an etag.
            try {
                $ifRangeDate = new \DateTime($ifRange);

                // It's a date. We must check if the entity is modified since
                // the specified date.
                if (!isset($httpHeaders['Last-Modified'])) $ignoreRangeHeader = true;
                else {
                    $modified = new \DateTime($httpHeaders['Last-Modified']);
                    if($modified > $ifRangeDate) $ignoreRangeHeader = true;
                }

            } catch (\Exception $e) {

                // It's an entity. We can do a simple comparison.
                if (!isset($httpHeaders['ETag'])) $ignoreRangeHeader = true;
                elseif ($httpHeaders['ETag']!==$ifRange) $ignoreRangeHeader = true;
            }
        }

        // We're only going to support HTTP ranges if the backend provided a filesize
        if (!$ignoreRangeHeader && $nodeSize && $range) {

            // Determining the exact byte offsets
            if (!is_null($range[0])) {

                $start = $range[0];
                $end = $range[1]?$range[1]:$nodeSize-1;
                if($start >= $nodeSize)
                    throw new Exception\RequestedRangeNotSatisfiable('The start offset (' . $range[0] . ') exceeded the size of the entity (' . $nodeSize . ')');

                if($end < $start) throw new Exception\RequestedRangeNotSatisfiable('The end offset (' . $range[1] . ') is lower than the start offset (' . $range[0] . ')');
                if($end >= $nodeSize) $end = $nodeSize-1;

            } else {

                $start = $nodeSize-$range[1];
                $end  = $nodeSize-1;

                if ($start<0) $start = 0;

            }

            // New read/write stream
            $newStream = fopen('php://temp','r+');

            // stream_copy_to_stream() has a bug/feature: the `whence` argument
            // is interpreted as SEEK_SET (count from absolute offset 0), while
            // for a stream it should be SEEK_CUR (count from current offset).
            // If a stream is nonseekable, the function fails. So we *emulate*
            // the correct behaviour with fseek():
            if ($start > 0) {
                if (($curOffs = ftell($body)) === false) $curOffs = 0;
                fseek($body, $start - $curOffs, SEEK_CUR);
            }
            stream_copy_to_stream($body, $newStream, $end-$start+1);
            rewind($newStream);

            $response->setHeader('Content-Length', $end-$start+1);
            $response->setHeader('Content-Range','bytes ' . $start . '-' . $end . '/' . $nodeSize);
            $response->setStatus(206);
            $response->setBody($newStream);

        } else {

            if ($nodeSize) $response->setHeader('Content-Length',$nodeSize);
            $response->setStatus(200);
            $response->setBody($body);

        }
        // Sending back false will interupt the event chain and tell the server
        // we've handled this method.
        return false;


    }

}
