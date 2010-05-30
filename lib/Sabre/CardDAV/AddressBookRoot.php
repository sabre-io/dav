<?php

/**
 * AddressBook rootnode 
 *
 * This object lists a collection of users, which can contain addressbooks.
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CardDAV_AddressBookRoot extends Sabre_DAV_Directory {

    /**
     * Authentication Backend 
     * 
     * @var Sabre_DAV_Auth_Backend_Abstract 
     */
    protected $authBackend;

    /**
     * CardDAV backend 
     * 
     * @var Sabre_CardDAV_Backend_Abstract 
     */
    protected $carddavBackend;

    /**
     * Constructor 
     *
     * This constructor needs both an authentication and a carddav backend.
     *
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     * @param Sabre_CardDAV_Backend_Abstract $carddavBackend 
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend,Sabre_CardDAV_Backend_Abstract $carddavBackend) {

        $this->authBackend = $authBackend;
        $this->carddavBackend = $carddavBackend;

    }

    /**
     * Returns the name of the node 
     * 
     * @return string 
     */
    public function getName() {

        return Sabre_CardDAV_Plugin::ADDRESSBOOK_ROOT;

    }

    /**
     * Returns the list of users as Sabre_CardDAV_UserAddressBooks objects. 
     * 
     * @return array 
     */
    public function getChildren() {

        $users = $this->authBackend->getUsers();
        $children = array();
        foreach($users as $user) {

            $children[] = new Sabre_CardDAV_UserAddressBooks($this->authBackend, $this->carddavBackend, $user['uri']);

        }
        return $children;

    }

}
