<?php

/**
 * Users collection 
 *
 * This object is responsible for generating a collection of users.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_CalendarRootNode extends Sabre_DAVACL_AbstractPrincipalCollection {

    /**
     * CalDAV backend 
     * 
     * @var Sabre_CalDAV_Backend_Abstract 
     */
    protected $caldavBackend;

    /**
     * Constructor 
     *
     * This constructor needs both an authentication and a caldav backend.
     *
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     * @param Sabre_CalDAV_Backend_Abstract $caldavBackend 
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend,Sabre_CalDAV_Backend_Abstract $caldavBackend) {

        parent::__construct($authBackend, Sabre_CalDAV_Plugin::CALENDAR_ROOT);
        $this->caldavBackend = $caldavBackend;

    }

    /**
     * This method returns a node for a principal.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     * 
     * @param array $principal 
     * @return Sabre_DAV_INode 
     */
    public function getChildForPrincipal(array $principal) {

        return new Sabre_CalDAV_UserCalendars($this->authBackend, $this->caldavBackend, $principal['uri']);

    }

}
