<?php

namespace Sabre\CalDAV\Notifications;
use Sabre\DAV;

/**
 * This interface reflects a single notification type.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface INotificationType extends DAV\PropertyInterface {

    /**
     * Serializes the notification as a single property.
     *
     * You should usually just encode the single top-level element of the
     * notification.
     *
     * @param Sabre\DAV\Server $server
     * @param DOMElement $node
     * @return void
     */
    function serialize(DAV\Server $server, \DOMElement $node);

    /**
     * This method serializes the entire notification, as it is used in the
     * response body.
     *
     * @param Sabre\DAV\Server $server
     * @param DOMElement $node
     * @return void
     */
    function serializeBody(DAV\Server $server, \DOMElement $node);

    /**
     * Returns a unique id for this notification
     *
     * This is just the base url. This should generally be some kind of unique
     * id.
     *
     * @return string
     */
    function getId();

}
