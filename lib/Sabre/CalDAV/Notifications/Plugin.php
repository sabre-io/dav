<?php

/**
 * This plugin implements the 'caldav-notifications' spec, as defined here:
 *
 * http://svn.calendarserver.org/repository/calendarserver/CalendarServer/trunk/doc/Extensions/caldav-notifications.txt
 *
 * This specification defines a standard way to send notifications back
 * to a user. Notifications are specific to a user.
 * 
 * @package Sabre
 * @subpackage DAVNotifications
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Notifications_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * This is the path that will be used by default to determine the 
     * notification url. The principal basename will be appended to this path 
     * to determine the final path. 
     */
    const NOTIFICATION_ROOT = 'notifications';

    /**
     * Calendarserver xml namespace
     */
    const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

    /**
     * Reference to the server object
     * 
     * @var Sabre_DAV_Server 
     */
    protected $server;

    /**
     * This initializes the plugin.
     *
     * This function is called by Sabre_DAV_Server, after
     * addPlugin is called.
     *
     * This method should set up the requires event subscriptions.
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;

        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));

        // Registring default namespaces
        $server->xmlNamespaces[self::NS_CALENDARSERVER] = 'cs';

        // Adding to the list of protected properties
        array_push($server->protectedProperties,
            '{' . self::NS_CALENDARSERVER . '}notification-URL',
            '{' . self::NS_CALENDARSERVER . '}notificationtype'
        );

        $server->resourceTypeMapping['Sabre_CalDAV_Notifications_INotifications'] = '{' . self::NS_CALENDARSERVER . '}notifications';
        $server->resourceTypeMapping['Sabre_CalDAV_Notifications_INotification'] = '{' . self::NS_CALENDARSERVER . '}notification';

    }

    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched.
     *
     * @param string $path
     * @param Sabre_DAV_INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, Sabre_DAV_INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof Sabre_DAVACL_IPrincipal) {

            // calendar-home-set property
            $notificationUrl = '{' . self::NS_CALENDARSERVER . '}notification-URL';
            if ($index = array_search($notificationUrl, $requestedProperties)) {
                $principalId = $node->getName();
                $calendarHomePath = self::NOTIFICATION_ROOT . '/' . $principalId . '/';
                unset($requestedProperties[$index]);
                $returnedProperties[200][$notificationUrl] = new Sabre_DAV_Property_Href($calendarHomePath);
            }

        } // instanceof IPrincipal

        if ($node instanceof Sabre_CalDAV_Notifications_INotification) {

            $propertyName = '{' . self::NS_CALENDARSERVER . '}notificationtype';
            if ($index = array_search($propertyName, $requestedProperties)) {

                $returnedProperties[200][$propertyName] =
                    $node->getNotificationType();

                unset($requestedProperties[$index]);        

            }

        }

    }

}
