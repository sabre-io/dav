<?php

/**
 * Principals CardDAV Backend class
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * This is a special backend to map WebDAV principals to a read-only addressbook
 */
class Sabre_CardDAV_Backend_Directory extends Sabre_CardDAV_Backend_Abstract {

    /**
     * Auth backend 
     * 
     * @var Sabre_DAV_Auth_Backend_Abstract 
     */
    protected $authBackend;

    /**
     * Creates the Directory backend 
     * 
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend) {

        $this->authBackend = $authBackend;

    }

    /**
     * Returns the list of addressbooks for a specific user.
     *
     * In our case only 1 addressbook exists.
     * 
     * @param string $principalUri 
     * @return array 
     */
    public function getAddressBooksForUser($principalUri) {

        return array(
            array(
                'id'  => 'directory',
                'uri' => 'directory',
                'principalUri' => '',
            )
        );

    }

    /**
     * Returns all cards for a specific addressbook id. 
     * 
     * @param string $addressbookId 
     * @return array 
     */
    public function getCards($addressbookId) {

        $users = $this->authBackend->getUsers();

        $cards = array();

        foreach($users as $user) {

            list(, $userName) = Sabre_DAV_URLUtil::splitPath($user['uri']);

            $carddata = array(
                "BEGIN:VCARD",
                "VERSION:3.0",
                "NICKNAME:" . $userName,
                "UID:" . $user['uri'],
                "FN:" . (isset($user['{DAV:}displayname'])?$user["{DAV:}displayname"]:$userName),
                "EMAIL:" . (isset($user['{' . Sabre_DAV_Server::NS_SABREDAV . '}email-address'])?$user['{' . Sabre_DAV_Server::NS_SABREDAV . '}email-address']:''),
                "END:VCARD",
            );

            $cards[] = array(
                'uri' => $userName,
                'carddata' => implode("\r\n",$carddata),
            );

        }

        return $cards;

    }

}
