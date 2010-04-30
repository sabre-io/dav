<?php

/**
 * Abstract Backend class
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * This class serves as a base-class for addressbooks
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
    public function createCard($addressBookId, $cardUri, $cardData) {

        throw new Sabre_DAV_Exception_Forbidden('Creating new cards is not supported');

    }

    /**
     * Updates a card
     * 
     * @param mixed $addressBookId 
     * @param string $cardUri 
     * @param string $cardData 
     * @return bool 
     */
    public function updateCard($addressBookId, $cardUri, $cardData) {

        throw new Sabre_DAV_Exception_Forbidden('Updating cards is not supported');

    }


    /**
     * Deletes a card
     * 
     * @param mixed $addressBookId 
     * @param string $cardUri 
     * @return bool 
     */
    public function deleteCard($addressBookId, $cardUri) {

        throw new Sabre_DAV_Exception_Forbidden('Deleting cards is not supported');

    }

}
