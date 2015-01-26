<?php

namespace Sabre\DAVACL\Sharing;

use Sabre\DAV\INode;
use Sabre\DAV\Property\Href;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAVACL\IPrincipal;


/**
 * Sharing Plugin
 *
 * This plugin provides functionality to support dav-resource-sharing.
 * Resource sharing is a specification that allows a user to 'share' a
 * collection, or calendar or addressbook with another user.
 *
 * Specifications related to sharing can be found here:
 *
 * https://tools.ietf.org/html/draft-pot-webdav-resource-sharing
 *
 * In addition to sharing, this plugin also implements everything needed for
 * webdav-notifications.
 *
 * The notifications system can be used to deliver notifications to the user
 * for now this system is only used in the 'sharing system', which is why
 * this feature is currently integrated here. 
 * 
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/) 
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends ServerPlugin {

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

        $server->on('propFind', [$this, 'propFind']);

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

        return 'notifications';

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
            'description' => 'This plugin implements webdav-notifications and webdav-resource-sharing.',
            'link'        => 'http://sabre.io/dav/sharing/',
        ];

    }

    /**
     * This event gets called when WebDAV properties are requested for a node.
     *
     * Intercepting this event allows us to add additional WebDAV properties.
     */
    function propFind(PropFind $propFind, INode $node) {

        if ($node instanceof IPrincipal) {

            $propFind->handle('{DAV:}notification-URL', function() use ($node) {

                return new Href($node->getPrincipalURL() . '/notifications/');

            });

        }

    }

}
