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
class Sabre_CalDAV_Notifications_Notification implements Sabre_CalDAV_Notifications_INotification {

    /**
     * The notification backend
     * 
     * @var Sabre_CalDAV_Notifications_Backend_Abstract 
     */
    protected $notificationBackend;

    /**
     * The actual notification
     * 
     * @var Sabre_CalDAV_Notifications_INotificationType 
     */
    protected $notification;
        
    /**
     * Constructor
     *
     * @param Sabre_CalDAV_Notifications_Backend_Abstract $notificationBackend
     * @param Sabre_CalDAV_Notifications_INotificationType $notification
     */
    public function __construct(Sabre_CalDAV_Notifications_Backend_Abstract $notificationBackend, Sabre_CalDAV_Notifications_INotificationType $notification) {        

        $this->notificationBackend = $notificationBackend;
        $this->notification = $notification;

    }

    /**
     * Returns the path name for this notification
     * 
     * @return id 
     */
    public function getName() {

        return $this->notification->getUrl();

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


    }

}
