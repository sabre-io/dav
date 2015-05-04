<?php

namespace Sabre\DAVSharing;

use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;

/**
 * This plugin adds support for 'sharing' to a WebDAV server.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin {

    protected $server;

    /**
     * Initialized the plugin.
     *
     * @param Server $server
     * @return void
     */
    function initialize(Server $server) {

        $this->server = $server;
        $this->server->xml->elementMap['{DAV:}share'] =
            'Sabre\\DAVSharing\\Xml\\Request\\ShareResource';

        $server->on('propfind', [$this, 'propFind']);
        $server->on('method:POST', [$this, 'httpPost']);

    }

    /**
     * This method is trigged when a system requests properties for a node.
     *
     * We intercept the propFind event to ensure that dav-sharing related
     * properties are returned
     *
     * @param PropFind $propFind
     * @return void
     */
    function propFind(PropFind $propFind, INode $node) {

        if ($node instanceof IShareableNode) {
            // We need to make sure that the resourcetype for the node
            // contains `{DAV:}shared-owner` if it is shared by the user.
            if ($rt = $propFind->get('{DAV:}resourcetype')) {
                if (count($node->getShares()) > 0) {
                    $rt->add('{DAV:}shared-owner');
                }
            }
        }

    }

    /**
     * Handles the POST request.
     *
     * This allows us to intercept POST requests related to webdav
     * resource-sharing.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    function httpPost(RequestInterface $request, ResponseInterface $response) {

        $result = Sabre\HTTP\negotiateContentType(
            ['application/davshare+xml']
            $request->getHeader('Content-Type')
        );
        if ($result!=='application/davshare+xml') {
            // Not the right content-type, abort!
            return;
        }

        // Parsing!
        $shareResource = $this->server->xml->expect(
            '{DAV:}share-resource',
            $request->getBody()
        );

        $this->share(
            $request->getPath(),
            $shareResource->mutations
        );

        $response->setStatus(204);

        // Returning false to break the method event change.
        return false;

    }

    /**
     * Shares a node
     *
     * @param string $path
     * @param array $mutations List of new, updated and deleted sharees.
     * @return void
     */
    function share($path, $mutations) {

        // Findind the node we need to share.
        $node = $this->server->tree->getNodeForPath(
            $request->getPath()
        );

        // Can the node support sharing?
        if (!$node instanceof IShareable) {
            throw new Forbidden('This node does not support sharing.');
        }

        // Is sharing allowed?
        $aclPlugin = $this->server->getPlugin('acl');
        $aclPlugin->checkPrivileges($path, '{DAV:}share');

        $node->updateInvitees($mutations);

    }


}
