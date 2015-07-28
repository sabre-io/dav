<?php

namespace Sabre\DAV\Sharing;

use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * This plugin implements HTTP requests and properties related to:
 *
 * draft-pot-webdav-resource-sharing-02
 *
 * This specification allows people to share webdav resources with others.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends ServerPlugin {

    /**
     * Reference to SabreDAV server object.
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     * This method should return a list of server-features.
     *
     * This is for example 'versioning' and is added to the DAV: header
     * in an OPTIONS response.
     *
     * @return array
     */
    function getFeatures() {

        return ['resource-sharing'];

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    function getPluginName() {

        return 'sharing';

    }

    /**
     * This initializes the plugin.
     *
     * This function is called by Sabre\DAV\Server, after
     * addPlugin is called.
     *
     * This method should set up the required event subscriptions.
     *
     * @param Server $server
     * @return void
     */
    function initialize(Server $server) {

        $this->server = $server;

        $server->xml->elementMap['{DAV:}share-resource'] = 'Sabre\\DAV\\Xml\\Request\\ShareResource';
        $server->on('method:POST',  [$this, 'httpPost']);

    }

    /**
     * We intercept this to handle POST requests on shared resources
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return null|bool
     */
    function httpPost(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();
        $contentType = $request->getHeader('Content-Type');

        // We're only interested in the davsharing content type.
        if (strpos($contentType, 'application/davsharing+xml') === false) {
            return;
        }

        $message = $this->server->xml->parse(
            $request->getBody(),
            $request->getUrl(),
            $documentType
        );

        switch ($documentType) {

            case '{DAV:}share-resource':

                $this->shareResource($path, $message->set, $message->remove);
                $response->setStatus(200);
                // Adding this because sending a response body may cause issues,
                // and I wanted some type of indicator the response was handled.
                $response->setHeader('X-Sabre-Status', 'everything-went-well');

                // Breaking the event chain
                return false;

            default :
                throw new BadRequest('Unexpected document type: ' . $documentType . ' for this Content-Type');

        }

    }

    /**
     * Updates the list of sharees on a shared resource.
     *
     * The set array is a list of people that are to be added to the
     * shared resource.
     *
     * Every element in the add array has the following properties:
     *   * href - A url. Usually a mailto: address
     *   * summary - A description of the share, can also be false
     *   * readOnly - A boolean value
     *
     * In addition to that, the array might have any additional properties,
     * specified in clark-notation, such as '{DAV:}displayname'.
     *
     * Every element in the remove array is just the url of the sharee that's
     * to be removed.
     *
     * @param string $path
     * @param array $set
     * @param array $remove
     * @return void
     */
    function shareResource($path, $set, $remove) {

        try {
            $node = $this->server->tree->getNodeForPath($path);
        } catch (DAV\Exception\NotFound $e) {
            // If the target node is not found, we stop executing.
            return;
        }

        if (!$node instanceof IShareableNode) {

            throw new Forbidden('Sharing is not allowed on this node');

        }

        // Getting ACL info
        $acl = $this->server->getPlugin('acl');

        // If there's no ACL support, we allow everything
        if ($acl) {
            $acl->checkPrivileges($path, '{DAV:}share');
        }

        $node->updateShares($set, $remove);

    }

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'This plugin implements WebDAV resource sharing',
            'link'        => 'https://github.com/evert/webdav-sharing'
        ];

    }

}
