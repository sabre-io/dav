<?php

/**
 * X-Sendfile plugin
 *
 * This plugin provides support for the X-Sendfile header to a WebDAV server.
 * The server needs to use mod_xsendfile or other implementations, and your
 * file nodes need to implement the Sabre_DAV_XSendFile_IFile interface.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Markus Koller
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_XSendFile_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * server
     *
     * @var Sabre_DAV_Server
     */
    private $server;

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
        $server->subscribeEvent('beforeMethod',array($this,'beforeMethod'));

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

        return 'xsendfile';

    }

    /**
     * This method is called before the logic for any HTTP method is
     * handled.
     *
     * This plugin uses that feature to intercept GET requests.
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function beforeMethod($method, $uri) {

        if ($method == 'GET') {

            $node = $this->server->tree->getNodeForPath($uri, 0);

            /* Run the default GET handler if the node doesn't implement the correct interface,
             * or the preconditions aren't met
             */
            if (!$node instanceof Sabre_DAV_XSendFile_IFile) return true;
            if (!$this->server->checkPreconditions(true)) return true;

            /*
             * TODO: getetag, getlastmodified, getsize should also be used using
             * this method
             */
            $httpHeaders = $this->server->getHTTPHeaders($uri);

            // Send the physical path of the file in the X-Sendfile header
            $httpHeaders['X-Sendfile'] = $node->getPhysicalPath();

            /* ContentType needs to get a default, because many webservers will otherwise
             * default to text/html, and we don't want this for security reasons.
             */
            if (!isset($httpHeaders['Content-Type'])) {
                $httpHeaders['Content-Type'] = 'application/octet-stream';
            }

            $this->server->httpResponse->setHeaders($httpHeaders);

            // Don't run the default GET handler
            return false;
        }

        return true;

    }

}
