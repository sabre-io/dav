<?php

/**
 * UserAddressBooks class
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * The UserAddressBooks collection contains a list of addressbooks associated with a user
 */
class Sabre_CardDAV_UserAddressBooks implements Sabre_DAV_IExtendedCollection {

    /**
     * Authentication backend 
     * 
     * @var Sabre_DAV_Auth_Backend_Abstract 
     */
    protected $authBackend;

    /**
     * Principal uri
     * 
     * @var array 
     */
    protected $userUri;

    /**
     * carddavBackend 
     * 
     * @var Sabre_CardDAV_Backend_Abstract 
     */
    protected $carddavBackend;

    /**
     * Constructor 
     * 
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     * @param Sabre_CardDAV_Backend_Abstract $carddavBackend 
     * @param string $userUri 
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend, Sabre_CardDAV_Backend_Abstract $carddavBackend, $userUri) {

        $this->authBackend = $authBackend;
        $this->carddavBackend = $carddavBackend;
        $this->userUri = $userUri;
       
    }

    /**
     * Returns the name of this object 
     * 
     * @return string
     */
    public function getName() {
      
        list(,$name) = Sabre_DAV_URLUtil::splitPath($this->userUri);
        return $name; 

    }

    /**
     * Updates the name of this object 
     * 
     * @param string $name 
     * @return void
     */
    public function setName($name) {

        throw new Sabre_DAV_Exception_Forbidden();

    }

    /**
     * Deletes this object 
     * 
     * @return void
     */
    public function delete() {

        throw new Sabre_DAV_Exception_Forbidden();

    }

    /**
     * Returns the last modification date 
     * 
     * @return int 
     */
    public function getLastModified() {

        return null; 

    }

    /**
     * Creates a new file under this object.
     *
     * This is currently not allowed
     * 
     * @param string $filename 
     * @param resource $data 
     * @return void
     */
    public function createFile($filename, $data=null) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating new files in this collection is not supported');

    }

    /**
     * Creates a new directory under this object.
     *
     * This is currently not allowed.
     * 
     * @param string $filename 
     * @return void
     */
    public function createDirectory($filename) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating new collections in this collection is not supported');

    }

    /**
     * Returns a single calendar, by name 
     * 
     * @param string $name
     * @todo needs optimizing
     * @return Sabre_CardDAV_AddressBook
     */
    public function getChild($name) {

        foreach($this->getChildren() as $child) {
            if ($name==$child->getName())
                return $child;

        }
        throw new Sabre_DAV_Exception_FileNotFound('Addressbook with name \'' . $name . '\' could not be found');

    }

    /**
     * Returns a list of addressbooks 
     * 
     * @return array 
     */
    public function getChildren() {

        $addressbooks = $this->carddavBackend->getAddressbooksForUser($this->userUri);
        $objs = array();
        foreach($addressbooks as $addressbook) {
            $objs[] = new Sabre_CardDAV_AddressBook($this->authBackend, $this->carddavBackend, $addressbook);
        }
        return $objs;

    }

    /**
     * Creates a new calendar
     * 
     * @param string $name 
     * @param string $properties 
     * @return void
     */
    public function createExtendedCollection($name, array $resourceType, array $properties) {

        throw new Sabre_DAV_Exception_Forbidden();
        if (!in_array('{urn:ietf:params:xml:ns:caldav}calendar',$resourceType) || count($resourceType)!==2) {
            throw new Sabre_DAV_Exception_InvalidResourceType('Unknown resourceType for this collection');
        }
        $this->caldavBackend->createCalendar($this->userUri, $name, $properties);

    }

}
