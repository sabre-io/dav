<?php

namespace Sabre\CalDAV\Subscriptions;

use
    Sabre\DAV\ServerPlugin,
    Sabre\DAV\Server;

/**
 * This plugin adds calendar-subscription support to your CalDAV server.
 *
 * Some clients support 'managed subscriptions' server-side. This is basically
 * a list of subscription urls a user is using.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
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
    public function initialize(Server $server) {

        $server->resourceTypeMapping['Sabre\\CalDAV\\Subscriptions\\ISubscription'] =
            '{http://calendarserver.org/ns/}subscribed';

        $server->propertyMap['{http://calendarserver.org/ns/}source'] =
            'Sabre\\DAV\\Property\\Href';

        $server->on('afterGetProperties', [$this, 'afterGetProperties']);

    }

    /**
     * This method should return a list of server-features.
     *
     * This is for example 'versioning' and is added to the DAV: header
     * in an OPTIONS response.
     *
     * @return array
     */
    public function getFeatures() {

        return array('calendarserver-subscribed');

    }

    /**
     * Triggered after properties have been fetched.
     *
     * @return void
     */
    public function afterGetProperties($path, &$properties, \Sabre\DAV\INode $node) {

        // There's a bunch of properties that must appear as a self-closing
        // xml-element. This event handler ensures that this will be the case.
        $props = [
            '{http://calendarserver.org/ns/}subscribed-strip-alarms',
            '{http://calendarserver.org/ns/}subscribed-strip-attachments',
            '{http://calendarserver.org/ns/}subscribed-strip-todos',
        ];

        foreach($props as $prop) {

            if (isset($properties[200][$prop])) {
                $properties[200][$prop] = '';
            }

        }

    }

}
