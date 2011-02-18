<?php

/**
 * AddressBook class
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * The AddressBook class represents a CardDAV addressbook
 *
 * The AddressBook can contain multiple vcards
 */
class Sabre_CardDAV_AddressBook implements Sabre_DAV_ICollection, Sabre_DAV_IProperties {

    /**
     * This is an array with addressbook information 
     * 
     * @var array 
     */
    private $addressBookId;

    /**
     * CardDAV backend 
     * 
     * @var Sabre_CardDAV_Backend_Abstract 
     */
    private $carddavBackend;

    /**
     * Constructor 
     * 
     * @param Sabre_CardDAV_Backend_Abstract $carddavBackend 
     * @param array $addressBookInfo 
     * @return void
     */
    public function __construct(Sabre_CardDAV_Backend_Abstract $carddavBackend,$addressBookId) {

        $this->carddavBackend = $carddavBackend;
        $this->addressBookId = $addressBookId;


    }

    /**
     * Returns the name of the addressbook 
     * 
     * @return string 
     */
    public function getName() {

        return $this->addressBookId;

    }

    /**
     * Updates properties such as the display name and description 
     * 
     * @param array $mutations 
     * @return array 
     */
    public function updateProperties($mutations) {

        throw new Sabre_DAV_Exception_Forbidden('Updating this addressbook is not supported');

    }

    /**
     * Returns the list of properties 
     * 
     * @param array $properties 
     * @return array 
     */
    public function getProperties($requestedProperties) {

        $response = array();

        foreach($requestedProperties as $prop) switch($prop) {

            case '{DAV:}resourcetype' : 
                $response[$prop] =  new Sabre_DAV_Property_ResourceType(array('{'.Sabre_CardDAV_Plugin::NS_CARDDAV.'}addressbook','{DAV:}collection')); 
                break;


        }
        return $response;

    }

    /**
     * Returns a card
     *
     * @param string $name 
     * @return Sabre_DAV_Card
     */
    public function getChild($name) {

        $obj = $this->carddavBackend->getCard($this->addressBookId,$name);
        if (!$obj) throw new Sabre_DAV_Exception_FileNotFound('Card not found');
        return new Sabre_CardDAV_Card($this->carddavBackend,array('id' => $this->addressBookId),$obj);

    }

    /**
     * Returns the full list of cards
     * 
     * @return array 
     */
    public function getChildren() {

        $objs = $this->carddavBackend->getCards($this->addressBookId);
        $children = array();
        foreach($objs as $obj) {
            $children[] = new Sabre_CardDAV_Card($this->carddavBackend,array('id' => $this->addressBookId),$obj);
        }
        return $children;

    }

    /**
     * Creates a new directory
     *
     * We actually block this, as subdirectories are not allowed in addressbooks. 
     * 
     * @param string $name 
     * @return void
     */
    public function createDirectory($name) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating collections in addressbooks is not allowed');

    }

    /**
     * Creates a new file
     *
     * The contents of the new file must be a valid VCARD
     * 
     * @param string $name 
     * @param resource $vcardData 
     * @return void
     */
    public function createFile($name,$vcardData = null) {

        $vcardData = stream_get_contents($vcardData);
        $this->carddavBackend->createCard($this->addressBookId,$name,$vcardData);

    }

    /**
     * Deletes the entire addressbook. 
     * 
     * @return void
     */
    public function delete() {

        throw new Sabre_DAV_Exception_Forbidden('Not supported yet');

    }

    /**
     * Renames the addressbook. Note that most calendars use the 
     * {DAV:}displayname to display a name to display a name. 
     * 
     * @param string $newName 
     * @return void
     */
    public function setName($newName) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Renaming this addressbooks is not supported');

    }

    /**
     * Returns the last modification date as a unix timestamp.
     * 
     * @return void
     */
    public function getLastModified() {

        return null;

    }

}
