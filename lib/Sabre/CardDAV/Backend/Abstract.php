<?php

/**
 * Abstract Backend class
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * This class serves as a base-class for addressbook backends
 *
 * Note that there are references to 'addressBookId' scattered throughout the 
 * class. The value of the addressBookId is completely up to you, it can be any 
 * arbitrary value you can use as an unique identifier.
 */
abstract class Sabre_CardDAV_Backend_Abstract {

    /**
     * Returns the list of addressbooks for a specific user. 
     * 
     * @param string $principalUri 
     * @return array 
     */
    public abstract function getAddressBooksForUser($principalUri); 

    /**
     * Updates an addressbook's properties
     *
     * See Sabre_DAV_IProperties for a description of the mutations array, as 
     * well as the return value. 
     *
     * @param mixed $addressBookId
     * @param array $mutations
     * @see Sabre_DAV_IProperties::updateProperties
     * @return bool|array
     */
    public abstract function updateAddressBook($addressBookId, array $mutations); 

    /**
     * Returns all cards for a specific addressbook id. 
     * 
     * @param mixed $addressbookId 
     * @return array 
     */
    public abstract function getCards($addressbookId); 

    /**
     * Returns a specfic card
     * 
     * @param mixed $addressBookId 
     * @param string $cardUri 
     * @return void
     */
    public function getCard($addressBookId, $cardUri) {

        foreach($this->getCards($addressBookId) as $card) {

            if ($card['uri'] === $cardUri) return $card;

        }

        return false;

    }

    /**
     * Creates a new card
     * 
     * @param mixed $addressBookId 
     * @param string $cardUri 
     * @param string $cardData 
     * @return bool 
     */
    abstract public function createCard($addressBookId, $cardUri, $cardData); 

    /**
     * Updates a card
     * 
     * @param mixed $addressBookId 
     * @param string $cardUri 
     * @param string $cardData 
     * @return bool 
     */
    abstract public function updateCard($addressBookId, $cardUri, $cardData); 

    /**
     * Deletes a card
     * 
     * @param mixed $addressBookId 
     * @param string $cardUri 
     * @return bool 
     */
    abstract public function deleteCard($addressBookId, $cardUri); 

}
