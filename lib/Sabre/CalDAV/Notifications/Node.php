<?php

/**
 * This node represents a single notification.
 *
 * The signature is mostly identical to that of Sabre_DAV_IFile, but the get() method 
 * MUST return an xml document that matches the requirements of the 
 * 'caldav-notifications.txt' spec.

 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Notifications_Node extends Sabre_DAV_File implements Sabre_CalDAV_Notifications_INode {

    /**
     * The notification backend
     * 
     * @var Sabre_CalDAV_Backend_NotificationSupport
     */
    protected $caldavBackend;

    /**
     * The actual notification
     * 
     * @var Sabre_CalDAV_Notifications_INotificationType 
     */
    protected $notification;
        
    /**
     * Constructor
     *
     * @param Sabre_CalDAV_Backend_NotificationSupport $caldavBackend
     * @param Sabre_CalDAV_Notifications_INotificationType $notification
     */
    public function __construct(Sabre_CalDAV_Backend_NotificationSupport $caldavBackend, Sabre_CalDAV_Notifications_INotificationType $notification) {        

        $this->caldavBackend = $caldavBackend;
        $this->notification = $notification;

    }

    /**
     * Returns the path name for this notification
     * 
     * @return id 
     */
    public function getName() {

        return $this->notification->getId();

    }

    /**
     * This method must return an xml element, using the 
     * Sabre_CalDAV_Notifications_INotificationType classes.
     * 
     * @return Sabre_DAVNotification_INotificationType
     */
    public function getNotificationType() {

        return $this->notification;

    }

    /**
     * Generates a notitification xml body. 
     * 
     * @return string 
     */
    public function get() {

        $dom = new \DOMDocument('1.0','utf-8');
        //$dom->formatOutput = true;
        $xnotification = $dom->createElement('cs:notification');
        $dom->appendChild($xnotification);

        $namespaces = array(
            'DAV:' => 'd',
            Sabre_CalDAV_Plugin::NS_CALENDARSERVER => 'cs',
        );

        // Adding in default namespaces
        foreach($namespaces as $namespace=>$prefix) {

            $xnotification->setAttribute('xmlns:' . $prefix,$namespace);

        }

        $this->notification->serializeBody($xnotification);

        return $dom->saveXML();

    }

    /**
     * Returns the mime-type for this node 
     *
     * @return string|null
     */
    public function getContentType() {

        return 'application/xml';

    }

    /**
     * Returns the file size
     *
     * We just return nothing here, so the Content-Length is not sent back.
     * 
     * @return void
     */
    public function getSize() {

        return null;

    }

}
