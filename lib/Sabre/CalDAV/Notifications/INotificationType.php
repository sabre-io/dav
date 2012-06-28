<?php

/**
 * This interface reflects a single notification type.
 *
 * @package Sabre
 * @subpackage CalDAV 
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_CalDAV_Notifications_INotificationType {

    /**
     * Serializes the notification as a single property.
     * 
     * You should usually just encode the single top-level element of the
     * notification. 
     * 
     * @param \DOMNode $node 
     * @return void
     */
    function serializeProperty(\DOMNode $node);

    /**
     * This method serializes the entire notification, as it is used in the
     * response body.
     * 
     * @param \DOMNode $node 
     * @return void
     */
    function serializeBody(\DOMNode $node);

    /**
     * Returns a unique id for this notification
     *
     * This is just the base url. This should generally be some kind of unique 
     * id.
     * 
     * @return string 
     */
    function getUrl() {

    }

}
