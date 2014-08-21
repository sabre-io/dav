<?php

namespace Sabre\DAV;

use
    Psr\Log\LoggerInterface,
    Psr\Log\LogLevel,
    DateTime,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface;

/**
 * Debugging Plugin
 *
 * This plugin injects itself into the server and logs a LOT of data.
 * This is for development purposes only. Using this plugin can greatly
 * increase memory usage.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class DebugPlugin extends ServerPlugin {

    protected $logger;
    protected $startTime;

    protected $contentTypeWhiteList = array(
        '#^text/(?!html|css)#',
        '#^application/xml#',
    );

    public function __construct(LoggerInterface $logger) {

        $this->logger = $logger;
        $this->startTime = time();

    }

    public function getPluginName() {

        return 'debuglogger';

    }

    /**
     * Initializes the plugin
     *
     * @param Server $server
     * @return void
     */
    public function initialize(Server $server) {

        $this->server = $server;
        $server->on('beforeMethod', [$this, 'logRequest'], 5);
        $server->on('exception', [$this, 'logResponse'], 200);
        $server->on('afterMethod', [$this, 'logResponse'], 200);
        $this->log(LogLevel::INFO, 'Initialized plugin. Request time ' . $this->startTime . ' (' . date(DateTime::RFC2822, $this->startTime) . '). Version: ' . Version::VERSION);

    }

    /**
     * Very first event to be triggered. This allows us to log the HTTP
     * request.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function logRequest(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath()?:'(root)';
        $this->log(LogLevel::INFO, 'REQUEST: ' . $request->getMethod() . ' ' . $path);

        $this->log(LogLevel::DEBUG, 'Plugins loaded:');
        foreach($this->server->getPlugins() as $pluginName => $plugin) {
            $this->log(LogLevel::DEBUG,'  ' . $pluginName . ' (' . get_class($plugin) . ')');
        }
        $this->log(LogLevel::DEBUG, 'SabreDAV server Base URI: ' . $this->server->getBaseUri());
        $this->log(LogLevel::DEBUG, 'Headers:');
        foreach($request->getHeaders() as $key=>$value) {
            if(strtolower($key) == 'authorization') {
                $this->log(LogLevel::DEBUG, '  '  . $key . ': ' . '<REDACTED>');
            } else {
                $this->log(LogLevel::DEBUG, '  '  . $key . ': ' . $value);
            }
        }

        // We're only going to show the request body if it's text-based. The
        // maximum size will be 10k.
        $contentType = $request->getHeader('Content-Type');
        $showBody = false;
        foreach($this->contentTypeWhiteList as $wl) {

            if (preg_match($wl, $contentType)) {
                $showBody = true;
                break;
            }

        }
        if ($showBody) {
            // We need to grab the body, and put it in an intermediate stream.
            $newBody = fopen('php://temp','r+');
            $body = $request->getBodyAsStream();

            // Only grabbing the first 10kb
            $strBody = fread($body, 10240);

            $this->log(LogLevel::DEBUG, 'Request body:');
            $this->log(LogLevel::DEBUG, $strBody);

            // Writing the bytes we already read
            fwrite($newBody, $strBody);

            // Writing the remainder of the input body, if there's anything
            // left.
            stream_copy_to_stream($body, $newBody);
            rewind($newBody);

            $request->setBody($newBody, true);

        }

    }

    /**
     * The last event to be triggered. This allows us to log the HTTP response.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function logResponse(RequestInterface $request, ResponseInterface $response) {

        $this->log(LogLevel::INFO, 'RESPONSE: ' . $response->getStatus() . ' ' . $response->getStatusText());

        $this->log(LogLevel::DEBUG, 'Headers:');
        foreach($response->getHeaders() as $key=>$value) {
            $this->log(LogLevel::DEBUG, '  '  . $key . ': ' . $value);
        }

        // We're only going to show the request body if it's text-based. The
        // maximum size will be 10k.
        $contentType = $response->getHeader('Content-Type');
        $showBody = false;
        foreach($this->contentTypeWhiteList as $wl) {

            if (preg_match($wl, $contentType)) {
                $showBody = true;
                break;
            }

        }
        if ($showBody) {
            // We need to grab the body, and put it in an intermediate stream.
            $newBody = fopen('php://temp','r+');
            $body = $response->getBodyAsStream();

            // Only grabbing the first 10kb
            $strBody = fread($body, 10240);

            $this->log(LogLevel::DEBUG, 'Response body:');
            $this->log(LogLevel::DEBUG, $strBody);

            // Writing the bytes we already read
            fwrite($newBody, $strBody);

            // Writing the remainder of the input body, if there's anything
            // left.
            stream_copy_to_stream($body, $newBody);
            rewind($newBody);

            $response->setBody($newBody, true);

        }

    }

    /**
     * Appends a message to the log
     *
     * @param int $logLevel
     * @param string $message
     * @return void
     */
    public function log($logLevel, $message) {

        $this->logger->log($logLevel, $message);

    }

}
