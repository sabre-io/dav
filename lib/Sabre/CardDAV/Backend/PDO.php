<?php

/**
 * PDO CardDAV backend
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * This CardDAV backend uses PDO to store addressbooks
 */
class Sabre_CardDAV_Backend_PDO extends Sabre_CardDAV_Backend_Abstract {

    /**
     * PDO connection 
     * 
     * @var PDO 
     */
    protected $pdo;

    /**
     * Sets up the object 
     * 
     * @param PDO $pdo 
     */
    public function __construct(PDO $pdo) {

        $this->pdo = $pdo;

    }

    /**
     * Returns the list of addressbooks for a specific user. 
     * 
     * @param string $principalUri 
     * @return array 
     */
    public function getAddressBooksForUser($principalUri) {

        $stmt = $this->pdo->prepare('SELECT id, uri, displayname, principaluri, description, ctag FROM addressbooks WHERE principaluri = ?');
        $result = $stmt->execute(array($principalUri));

        $addressBooks = array();

        foreach($stmt->fetchAll() as $row) {

            $addressBooks[] = array(
                'id'  => $row['id'],
                'uri' => $row['uri'],
                'principaluri' => $row['principaluri'],
                '{DAV:}displayname' => $row['displayname'],
                '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' => $row['description'],
                '{http://calendarserver.org/ns/}getctag' => $row['ctag'], 
            );

        }

        return $addressBooks;

    }

    /**
     * Returns all cards for a specific addressbook id. 
     * 
     * @param mixed $addressbookId 
     * @return array 
     */
    public function getCards($addressbookId) {

        $stmt = $this->pdo->prepare('SELECT id, carddata, uri, lastmodified FROM cards WHERE addressbookid = ?');
        $stmt->execute(array($addressbookId));

        return $stmt->fetchAll();

    
    }
    /**
     * Returns a specfic card
     * 
     * @param mixed $addressBookId 
     * @param string $cardUri 
     * @return array 
     */
    public function getCard($addressBookId, $cardUri) {

        $stmt = $this->pdo->prepare('SELECT id, carddata, uri, lastmodified FROM cards WHERE addressbookid = ? AND uri = ? LIMIT 1');
        $stmt->execute(array($addressBookId, $cardUri));

        $result = $stmt->fetchAll();

        return (count($result)>0?$result[0]:false);

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

        $stmt = $this->pdo->prepare('INSERT INTO cards (carddata, uri, lastmodified, addressbookid) VALUES (?, ?, ?, ?)');

        $result = $stmt->execute(array($cardData, $cardUri, time(), $addressBookId));

        $stmt2 = $this->pdo->prepare('UPDATE addressbooks SET ctag = ctag + 1 WHERE id = ?');
        $stmt2->execute(array($addressBookId));

        return $result;

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

        $stmt = $this->pdo->prepare('UPDATE cards SET carddata = ?, lastmodified = ? WHERE uri = ? AND addressbookid =?');
        $result = $stmt->execute(array($cardData, time(), $cardUri, $addressBookId));

        $stmt2 = $this->pdo->prepare('UPDATE addressbooks SET ctag = ctag + 1 WHERE id = ?');
        $stmt2->execute(array($addressBookId));

        return $stmt->rowCount()===1;

    }

    /**
     * Deletes a card
     * 
     * @param mixed $addressBookId 
     * @param string $cardUri 
     * @return bool 
     */
    public function deleteCard($addressBookId, $cardUri) {

        $stmt = $this->pdo->prepare('DELETE FROM cards WHERE addressbookid = ? AND uri = ?');
        $stmt->execute(array($addressBookId, $cardUri));

        $stmt2 = $this->pdo->prepare('UPDATE addressbooks SET ctag = ctag + 1 WHERE id = ?');
        $stmt2->execute(array($addressBookId));

        return $stmt->rowCount()===1;

    }
}
