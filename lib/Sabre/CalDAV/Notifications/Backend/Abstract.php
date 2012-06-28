<?php

/**
 * Abstract backend for notifications
 *
 * Implement this backend to create your own notification system.
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_CalDAV_Notifications_Backend_Abstract {

    /**
     * Returns a list of notifications for a given principal url.
     *
     * The returned array should only consist of implementations of
     * Sabre_CalDAV_Notifications_INotificationType. 
     * 
     * @param string $principalUri
     * @return array 
     */
    abstract function getNotificationsForPrincipal($principalUri);

    /**
     * This deletes a specific notifcation.
     *
     * This may be called by a client once it deems a notification handled. 
     * 
     * @param string $principalUri 
     * @param Sabre_CalDAV_Notifications_INotificationType $notification 
     * @return void
     */
    abstract function deleteNotification($principalUri, Sabre_CalDAV_Notifications_INotificationType $notification); 

}
