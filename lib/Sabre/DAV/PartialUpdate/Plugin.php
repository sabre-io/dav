<?php
/**
 * Partial update plugin (Patch method)
 *
 * This plugin provides a way to modify only part of a target resource
 * It may bu used to update a file chunk, upload big a file into smaller
 * chunks or resume an upload
 * While the Patch method has been proposed as a draft to the IEEE, It's
 * behaviour is not clearly defined. This implementation is not interoperable.
 *
 * $patchPlugin = new Sabre_DAV_Patch_Plugin();
 * $server->addPlugin($patchPlugin);
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Jean-Tiare LE BIGOT (http://www.jtlebi.fr/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

class patch_filter extends php_user_filter {

    /**
     * Filters a stream
     *
     * This methods allows live patching of a stream. It is used to make
     * available a preview for the 'beforeWriteContent' pipeline.
     *
     * @return INT status
     */
    function filter($in, $out, &$consumed, $closing, $start, $len, $body) {

        while ($bucket = stream_bucket_make_writeable($in)) {
            for($i=0; $i < $bucket->datalen; $i++) {
                $j = $consumed-$start+$i;

                if($j >= 0 && $j < $len && !feof($body)) {

                    $bucket->data[$i] = fgetc($body);

                }
            }
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);

        }

        return PSFS_PASS_ON;
    }

}

class Sabre_DAV_PartialUpdate_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * server
     *
     * @var Sabre_DAV_Server
     */
    private $server;

    /**
     * __construct
     */
    public function __construct() {

        stream_filter_register('patch', 'patch_filter');

    }

    /**
     * Initializes the plugin
     *
     * This method is automatically called by the Server class after addPlugin.
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('unknownMethod',array($this,'unknownMethod'));

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using Sabre_DAV_Server::getPlugin
     *
     * @return string
     */
    public function getPluginName() {

        return 'PartialUpdate';

    }

    /**
     * This method is called by the Server if the user used an HTTP method
     * the server didn't recognize.
     *
     * This plugin intercepts the PATCH methods.
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function unknownMethod($method, $uri) {

        switch($method) {
            
            case 'PATCH': $this->httpPatch($uri); return false;

        }

    }

    /**
     * Use this method to tell the server this plugin defines additional
     * HTTP methods.
     *
     * This method is passed a uri. It should only return HTTP methods that are
     * available for the specified uri.
     * 
     * We claim to support PATCH method (partial update) if and only if
     *     - the node exist
     *     - the node implements our partial update interface
     *
     * @param string $uri
     * @return array
     */
    public function getHTTPMethods($uri) {
        
        $tree = $this->server->tree;
        
        if ($tree->nodeExists($uri) && 
            $tree->getNodeForPath($uri) instanceof Sabre_DAV_PartialUpdate_IFile) {
            return array('PATCH');
         }
         
         return array();

    }

    /**
     * Returns a list of features for the HTTP OPTIONS Dav: header.
     *
     * In this case this is only the number 3. The 3 in the Dav: header
     * indicates the server supports Patch. => extensions
     *
     * @return array
     */
    public function getFeatures() {

        return array('sabredav-partialupdate');

    }

    /**
     * Patch an uri
     *
     * The WebDAV patch request can be used to modify only a part of an 
     * existing resource. If the resource does not exist yet and the first
     * offset is not 0, the request fails
     *
     * @param string $uri
     * @return void
     */
    protected function httpPatch($uri) {

        $range = $this->getHTTPUpdateRange();

        if (!$range) {
            throw new Sabre_DAV_Exception_BadRequest('No valid "X-Update-Range" found in the headers');
        }
        
        $contentType = $this->server->httpRequest->getHeader('Content-Type');
        
        if ($contentType != 'application/x-sabredav-partialupdate') {
            throw new Sabre_DAV_Exception_UnsupportedMediaType('Unknown Content-Type header "' . $contentType . '"');
        }

        $len = $this->server->httpRequest->getHeader('Content-Length');

        // Load the begin and end data
        $start = ($range[0])?$range[0]:0;
        $end   = ($range[1])?$range[1]:$len-1;

        // Check consistency
        if($end < $start) throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable('The end offset (' . $range[1] . ') is lower than the start offset (' . $range[0] . ')');
        if($end - $start + 1 != $len) throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable('Actual data length (' . $len . ') is not consistent with begin (' . $range[0] . ') and end (' . $range[1] . ') offsets');

        // Get the node. Will throw a 404 if not found
        $node = $this->server->tree->getNodeForPath($uri);
        
        if (!($node instanceof Sabre_DAV_PartialUpdate_IFile)) {
            throw new Sabre_DAV_Exception_MethodNotAllowed('Can not PATCH the requested resource.');
        }

        // Checking If-None-Match and related headers.
        if (!$this->server->checkPreconditions()) return;

        // If the node is a collection, we'll deny it
        if (!($node instanceof Sabre_DAV_IFile)) throw new Sabre_DAV_Exception_Conflict('PATCH is not allowed on non-files.');

        $filter = stream_filter_append($node->getReadonlyStream(), 'patch', STREAM_FILTER_READ, $start, $len, $body);
        if (!$this->server->broadcastEvent('beforeWriteContent',array($uri, $node, &$body))) return false;
        stream_filter_remove($filter);

        $body = $this->httpRequest->getBody();
        $etag = $node->putRange($body, $start);

        $this->server->broadcastEvent('afterWriteContent',array($uri, $node));

        $this->server->httpResponse->setHeader('Content-Length','0');
        if ($etag) $this->server->httpResponse->setHeader('ETag',$etag);
        $this->server->httpResponse->sendStatus(204);

    }
    
   /**
     * Returns the HTTP custom range update header
     *
     * This method returns null if there is no well-formed HTTP range request
     * header or array($start, $end).
     *
     * The first number is the offset of the first byte in the range.
     * The second number is the offset of the last byte in the range.
     *
     * If the second offset is null, it should be treated as the offset of the last byte of the entity
     * If the first offset is null, the second offset should be used to retrieve the last x bytes of the entity
     *
     * @return array|null
     */
    public function getHTTPUpdateRange() {

        $range = $this->server->httpRequest->getHeader('X-Update-Range');
        if (is_null($range)) return null;

        // Matching "Range: bytes=1234-5678: both numbers are optional

        if (!preg_match('/^bytes=([0-9]*)-([0-9]*)$/i',$range,$matches)) return null;

        if ($matches[1]==='' && $matches[2]==='') return null;

        return array(
            $matches[1]!==''?$matches[1]:null,
            $matches[2]!==''?$matches[2]:null,
        );

    }
}
