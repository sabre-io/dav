<?php

namespace Sabre\CalDAV\Notifications;

use Sabre\DAV;
use Sabre\CalDAV;

/**
 * This node represents a list of notifications.
 *
 * It provides no additional functionality, but you must implement this
 * interface to allow the Notifications plugin to mark the collection
 * as a notifications collection.
 *
 * This collection should only return Sabre\CalDAV\Notifications\INode nodes as
 * its children.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Collection extends DAV\Collection implements ICollection {

    /**
     * The notification backend
     *
     * @var Sabre\CalDAV\Backend\NotificationSupport
     */
    protected $caldavBackend;

    /**
     * Principal uri
     *
     * @var string
     */
    protected $principalUri;

    /**
     * Constructor
     *
     * @param Sabre\CalDAV\Backend\NotificationSupport $caldavBackend
     * @param string $principalUri
     */
    public function __construct(CalDAV\Backend\NotificationSupport $caldavBackend, $principalUri) {

        $this->caldavBackend = $caldavBackend;
        $this->principalUri = $principalUri;

    }

    /**
     * Returns all notifications for a principal
     *
     * @return array
     */
    public function getChildren() {

        $children = array();
        $notifications = $this->caldavBackend->getNotificationsForPrincipal($this->principalUri);

        foreach($notifications as $notification) {

            $children[] = new Node(
                $this->caldavBackend,
                $notification
            );
        }

        return $children;

    }

    /**
     * Returns the name of this object
     *
     * @return string
     */
    public function getName() {

        return 'notifications';

    }

}
