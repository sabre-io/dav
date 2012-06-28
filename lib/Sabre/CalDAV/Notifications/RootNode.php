<?php

/**
 * The notifications root node.
 *
 * This node generates a single child for every principal.
 * Each principal is a 'Notifications' object.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVNotification_RootNode extends Sabre_DAVACL_AbstractPrincipalCollection {

    /**
     * Notification backend
     *
     * @var Sabre_CalDAV_Notifications_Backend_Abstract
     */
    protected $notificationBackend;

    /**
     * Constructor
     *
     * @param Sabre_DAVACL_IPrincipalBackend $principalBackend
     * @param Sabre_CalDAV_Notifications_Backend_Abstract $notificationBackend
     * @param string $principalPrefix
     */
    public function __construct(Sabre_DAVACL_IPrincipalBackend $principalBackend,Sabre_CalDAV_Notifications_Backend_Abstract $notificationBackend, $principalPrefix = 'principals') {

        parent::__construct($principalBackend, $principalPrefix);
        $this->notificationBackend = $notificationBackend;

    }

    /**
     * This method is called whenever a node is instantiated for a specific
     * principal.
     *
     * @param array $principal
     * @return Sabre_CalDAV_Notifications_INotifications
     */
    public function getChildForPrincipal(array $principal) {

        return new Sabre_CalDAV_Notifications_Notifications($this->notificationsBackend, $principal['uri']);

    }

    /**
     * Returns this node's name
     *
     * @return string
     */
    public function getName() {

        return Sabre_CalDAV_Notifications_Plugin::NOTIFICATION_ROOT;

    }

}
