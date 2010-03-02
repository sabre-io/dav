<?php

/**
 * PDO CalDAV backend
 *
 * This backend is used to store calendar-data in a PDO database, such as 
 * sqlite or MySQL
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Backend_PDO extends Sabre_CalDAV_Backend_Abstract {

    /**
     * pdo 
     * 
     * @var PDO
     */
    private $pdo;

    /**
     * List of CalDAV properties, and how they map to database fieldnames
     *
     * Add your own properties by simply adding on to this array
     * 
     * @var array
     */
    public $propertyMap = array(
        '{DAV:}displayname'                          => 'displayname',
        '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
        '{http://apple.com/ns/ical/}calendar-order'  => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color'  => 'calendarcolor',
    );

    /**
     * Creates the backend 
     * 
     * @param PDO $pdo 
     */
    public function __construct(PDO $pdo) {

        $this->pdo = $pdo;

    }

    /**
     * Returns a list of calendars for a principal
     *
     * @param string $userUri 
     * @return array 
     */
    public function getCalendarsForUser($principalUri) {

        $fields = array_values($this->propertyMap);
        $fields[] = 'id';
        $fields[] = 'uri';
        $fields[] = 'ctag';

        // Making fields a comma-delimited list 
        $fields = implode(', ', $fields);
        $stmt = $this->pdo->prepare("SELECT " . $fields . " FROM calendars WHERE principalUri = ?"); 
        $stmt->execute(array($principalUri));

        $calendars = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $calendar = array(
                'id' => $row['id'],
                'uri' => $row['uri'],
                '{' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '}getctag' => $row['ctag'],
            );

            foreach($this->propertyMap as $xmlName=>$dbName) {
                $calendar[$xmlName] = $row[$dbName];
            }

            $calendars[] = $calendar;

        }

        return $calendars;

    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return mixed
     */
    public function createCalendar($principalUri,$calendarUri, array $properties) {

        $fieldNames = array(
            'principaluri',
            'uri',
            'ctag',
        );
        $values = array(
            ':principaluri' => $principalUri,
            ':uri'          => $calendarUri,
            ':ctag'         => 1,
        );

        foreach($this->propertyMap as $xmlName=>$dbName) {
            if (isset($properties[$xmlName])) {
                $values[':' . $dbName] = $properties[$xmlName];
                $fieldNames[] = $dbName;
            }
        }

        $stmt = $this->pdo->prepare("INSERT INTO calendars (".implode(', ', $fieldNames).") VALUES (".implode(', ',array_keys($values)).")");
        $stmt->execute($values);

        return $this->pdo->lastInsertId();

    }

    /**
     * Updates a calendar's properties
     *
     *
     * The mutations array has 3 elements for each item. The first indicates if the property
     * is to be removed or updated (Sabre_DAV_Server::PROP_REMOVE and Sabre_DAV_Server::PROP_SET)
     * the second is the propertyName in Clark notation, the third is the actual value (ommitted
     * if the property is to be deleted).
     *
     * The result of this method should be another array. Each element has 2 subelements with the 
     * propertyname and statuscode for the change
     *
     * For example:
     *   array(array('{DAV:}prop1',200), array('{DAV:}prop2',200), array('{DAV:}prop3',403))
     *
     * The default implementation does not allow any properties to be updated, and thus
     * will return 403 for each one.
     *
     * @param string $calendarId
     * @param array $mutations
     * @return array 
     */
    public function updateCalendar($calendarId, array $mutations) {

        $values = array();

        $result = array();

        foreach($mutations as $mutation) {

            // If the fieldname is not in the propertymap, we deny the update
            if (!isset($this->propertyMap[$mutation[1]])) {
                $result[] = array($mutation[1],403);
                continue;
            }
            $value = $mutation[0]===Sabre_DAV_Server::PROP_REMOVE?null:$mutation[2];
            $fieldName = $this->propertyMap[$mutation[1]];

            $values[$fieldName] = $value;

            // We're assuming early that the property update will succeed
            // if it doesn't, we'll get a SQL error anyway.
            $result[] = array($mutation[1],200);


        }
       
        // If the values array is empty, it means no supported
        // field are updated. The result should only contain 403 statuses
        if (count($values)===0) return $result;
        
        $valuesSql = array();
        foreach($values as $fieldName=>$value) {
            $valuesSql[] = $fieldName . ' = ?';
        }
        $valuesSql[] = 'ctag = ctag + 1';

        $stmt = $this->pdo->prepare("UPDATE calendars SET " . implode(', ',$valuesSql) . " WHERE id = ?");
        $values['id'] = $calendarId; 
        $stmt->execute(array_values($values));

        return $result;

    }

    /**
     * Delete a calendar and all it's objects 
     * 
     * @param string $calendarId 
     * @return void
     */
    public function deleteCalendar($calendarId) {

        $stmt = $this->pdo->prepare('DELETE FROM calendarobjects WHERE calendarid = ?');
        $stmt->execute(array($calendarId));

        $stmt = $this->pdo->prepare('DELETE FROM calendars WHERE id = ?');
        $stmt->execute(array($calendarId));

    }

    /**
     * Returns all calendar objects within a calendar object. 
     * 
     * @param string $calendarId 
     * @return array 
     */
    public function getCalendarObjects($calendarId) {

        $stmt = $this->pdo->prepare('SELECT * FROM calendarobjects WHERE calendarid = ?');
        $stmt->execute(array($calendarId));
        return $stmt->fetchAll();

    }

    /**
     * Returns information from a single calendar object, based on it's object uri. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @return array 
     */
    public function getCalendarObject($calendarId,$objectUri) {

        $stmt = $this->pdo->prepare('SELECT * FROM calendarobjects WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId, $objectUri));
        return $stmt->fetch();

    }

    /**
     * Creates a new calendar object. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    public function createCalendarObject($calendarId,$objectUri,$calendarData) {

        $stmt = $this->pdo->prepare('INSERT INTO calendarobjects (calendarid, uri, calendardata, lastmodified) VALUES (?,?,?,?)');
        $stmt->execute(array($calendarId,$objectUri,$calendarData,time()));
        $stmt = $this->pdo->prepare('UPDATE calendars SET ctag = ctag + 1 WHERE id = ?');
        $stmt->execute(array($calendarId));

    }

    /**
     * Updates an existing calendarobject, based on it's uri. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    public function updateCalendarObject($calendarId,$objectUri,$calendarData) {

        $stmt = $this->pdo->prepare('UPDATE calendarobjects SET calendardata = ?, lastmodified = ? WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarData,time(),$calendarId,$objectUri));
        $stmt = $this->pdo->prepare('UPDATE calendars SET ctag = ctag + 1 WHERE id = ?');
        $stmt->execute(array($calendarId));

    }

    /**
     * Deletes an existing calendar object. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @return void
     */
    public function deleteCalendarObject($calendarId,$objectUri) {

        $stmt = $this->pdo->prepare('DELETE FROM calendarobjects WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId,$objectUri));
        $stmt = $this->pdo->prepare('UPDATE calendars SET ctag = ctag + 1 WHERE id = ?');
        $stmt->execute(array($calendarId));

    }


}
