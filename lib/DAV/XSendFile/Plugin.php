<?php

namespace Sabre\DAV\XSendFile;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\PhysicalFile;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

    /**
     * Sets up the plugin and registers events. 
     * 
     * @param Server $server 
     * @return void
     */
    public function initialize(Server $server) {

        $this->on('method:GET', [$this,'httpGet'], 90);

    }

    /**
     * Handles GET requests 
     * 
     * @return void
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();
        $node = $this->server->tree->getNodeForPath($path,0);

        if (!$this->server->checkPreconditions(true)) return false;
        if (!$node instanceof IPhysicalFile) return;

        $physicalPath = $node->getPhysicalPath();

        $httpHeaders = $this->server->getHTTPHeaders($path);

        /* ContentType needs to get a default, because many webservers will otherwise
         * default to text/html, and we don't want this for security reasons.
         */
        if (!isset($httpHeaders['Content-Type'])) {
            $httpHeaders['Content-Type'] = 'application/octet-stream';
        }
        $httpHeaders['X-SendFile'] = $physicalPath;

        $response->addHeaders($httpHeaders);
        $response->setStatus(200);

        // Sending back false will interupt the event chain and tell the server
        // we've handled this method.
        return false;

    }

}
