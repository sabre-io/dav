<?php

namespace Sabre\CalDAV\Notifications;

use Sabre\DAV;
use Sabre\CalDAV;

/**
 * This node represents a single notification.
 *
 * The signature is mostly identical to that of Sabre\DAV\IFile, but the get() method
 * MUST return an xml document that matches the requirements of the
 * 'caldav-notifications.txt' spec.

 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Node extends DAV\Node implements INode {

    /**
     * The notification backend
     *
     * @var Sabre\CalDAV\Backend\NotificationSupport
     */
    protected $caldavBackend;

    /**
     * The actual notification
     *
     * @var Sabre\CalDAV\Notifications\INotificationType
     */
    protected $notification;

    /**
     * Constructor
     *
     * @param Sabre\CalDAV\Backend\NotificationSupport $caldavBackend
     * @param Sabre\CalDAV\Notifications\INotificationType $notification
     */
    public function __construct(CalDAV\Backend\NotificationSupport $caldavBackend, INotificationType $notification) {

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
     * Sabre\CalDAV\Notifications\INotificationType classes.
     *
     * @return Sabre\CalDAV\Notifications\INotificationType
     */
    public function getNotificationType() {

        return $this->notification;

    }

}
