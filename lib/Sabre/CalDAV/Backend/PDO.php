<?php

/**
 * PDO CalDAV backend
 *
 * This backend is used to store calendar-data in a PDO database, such as
 * sqlite or MySQL
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Backend_PDO extends Sabre_CalDAV_Backend_Abstract {

    /**
     * pdo
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * The table name that will be used for calendars
     *
     * @var string
     */
    protected $calendarTableName;

    /**
     * The table name that will be used for calendar objects
     *
     * @var string
     */
    protected $calendarObjectTableName;

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
        '{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => 'timezone',
        '{http://apple.com/ns/ical/}calendar-order'  => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color'  => 'calendarcolor',
    );

    /**
     * Creates the backend
     *
     * @param PDO $pdo
     * @param string $calendarTableName
     * @param string $calendarObjectTableName
     */
    public function __construct(PDO $pdo, $calendarTableName = 'calendars', $calendarObjectTableName = 'calendarobjects') {

        $this->pdo = $pdo;
        $this->calendarTableName = $calendarTableName;
        $this->calendarObjectTableName = $calendarObjectTableName;

    }

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri, which the basename of the uri with which the calendar is
     *    accessed.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * @param string $principalUri
     * @return array
     */
    public function getCalendarsForUser($principalUri) {

        $fields = array_values($this->propertyMap);
        $fields[] = 'id';
        $fields[] = 'uri';
        $fields[] = 'ctag';
        $fields[] = 'components';
        $fields[] = 'principaluri';

        // Making fields a comma-delimited list
        $fields = implode(', ', $fields);
        $stmt = $this->pdo->prepare("SELECT " . $fields . " FROM ".$this->calendarTableName." WHERE principaluri = ? ORDER BY calendarorder ASC");
        $stmt->execute(array($principalUri));

        $calendars = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $components = array();
            if ($row['components']) {
                $components = explode(',',$row['components']);
            }

            $calendar = array(
                'id' => $row['id'],
                'uri' => $row['uri'],
                'principaluri' => $row['principaluri'],
                '{' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '}getctag' => $row['ctag']?$row['ctag']:'0',
                '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet($components),
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
     * @return string
     */
    public function createCalendar($principalUri, $calendarUri, array $properties) {

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

        // Default value
        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        $fieldNames[] = 'components';
        if (!isset($properties[$sccs])) {
            $values[':components'] = 'VEVENT,VTODO';
        } else {
            if (!($properties[$sccs] instanceof Sabre_CalDAV_Property_SupportedCalendarComponentSet)) {
                throw new Sabre_DAV_Exception('The ' . $sccs . ' property must be of type: Sabre_CalDAV_Property_SupportedCalendarComponentSet');
            }
            $values[':components'] = implode(',',$properties[$sccs]->getValue());
        }

        foreach($this->propertyMap as $xmlName=>$dbName) {
            if (isset($properties[$xmlName])) {

                $values[':' . $dbName] = $properties[$xmlName];
                $fieldNames[] = $dbName;
            }
        }

        $stmt = $this->pdo->prepare("INSERT INTO ".$this->calendarTableName." (".implode(', ', $fieldNames).") VALUES (".implode(', ',array_keys($values)).")");
        $stmt->execute($values);

        return $this->pdo->lastInsertId();

    }

    /**
     * Updates properties for a calendar.
     *
     * The mutations array uses the propertyName in clark-notation as key,
     * and the array value for the property value. In the case a property
     * should be deleted, the property value will be null.
     *
     * This method must be atomic. If one property cannot be changed, the
     * entire operation must fail.
     *
     * If the operation was successful, true can be returned.
     * If the operation failed, false can be returned.
     *
     * Deletion of a non-existent property is always successful.
     *
     * Lastly, it is optional to return detailed information about any
     * failures. In this case an array should be returned with the following
     * structure:
     *
     * array(
     *   403 => array(
     *      '{DAV:}displayname' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}owner' => null,
     *   )
     * )
     *
     * In this example it was forbidden to update {DAV:}displayname.
     * (403 Forbidden), which in turn also caused {DAV:}owner to fail
     * (424 Failed Dependency) because the request needs to be atomic.
     *
     * @param string $calendarId
     * @param array $mutations
     * @return bool|array
     */
    public function updateCalendar($calendarId, array $mutations) {

        $newValues = array();
        $result = array(
            200 => array(), // Ok
            403 => array(), // Forbidden
            424 => array(), // Failed Dependency
        );

        $hasError = false;

        foreach($mutations as $propertyName=>$propertyValue) {

            // We don't know about this property.
            if (!isset($this->propertyMap[$propertyName])) {
                $hasError = true;
                $result[403][$propertyName] = null;
                unset($mutations[$propertyName]);
                continue;
            }

            $fieldName = $this->propertyMap[$propertyName];
            $newValues[$fieldName] = $propertyValue;

        }

        // If there were any errors we need to fail the request
        if ($hasError) {
            // Properties has the remaining properties
            foreach($mutations as $propertyName=>$propertyValue) {
                $result[424][$propertyName] = null;
            }

            // Removing unused statuscodes for cleanliness
            foreach($result as $status=>$properties) {
                if (is_array($properties) && count($properties)===0) unset($result[$status]);
            }

            return $result;

        }

        // Success

        // Now we're generating the sql query.
        $valuesSql = array();
        foreach($newValues as $fieldName=>$value) {
            $valuesSql[] = $fieldName . ' = ?';
        }
        $valuesSql[] = 'ctag = ctag + 1';

        $stmt = $this->pdo->prepare("UPDATE " . $this->calendarTableName . " SET " . implode(', ',$valuesSql) . " WHERE id = ?");
        $newValues['id'] = $calendarId;
        $stmt->execute(array_values($newValues));

        return true;

    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param string $calendarId
     * @return void
     */
    public function deleteCalendar($calendarId) {

        $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarObjectTableName.' WHERE calendarid = ?');
        $stmt->execute(array($calendarId));

        $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarTableName.' WHERE id = ?');
        $stmt->execute(array($calendarId));

    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * id - unique identifier which will be used for subsequent updates
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can be any arbitrary string.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * calendarid - The calendarid as it was passed to this function.
     *   * size - The size of the calendar objects, in bytes.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @param string $calendarId
     * @return array
     */
    public function getCalendarObjects($calendarId) {

        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, size FROM '.$this->calendarObjectTableName.' WHERE calendarid = ?');
        $stmt->execute(array($calendarId));

        $result = array();
        foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[] = array(
                'id'           => $row['id'],
                'uri'          => $row['uri'],
                'lastmodified' => $row['lastmodified'],
                'etag'         => '"' . $row['etag'] . '"',
                'calendarid'   => $row['calendarid'],
                'size'         => (int)$row['size'],
            );
        }

        return $result;

    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @return array
     */
    public function getCalendarObject($calendarId,$objectUri) {

        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, size, calendardata FROM '.$this->calendarObjectTableName.' WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId, $objectUri));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if(!$row) return null;

        return array(
            'id'           => $row['id'],
            'uri'          => $row['uri'],
            'lastmodified' => $row['lastmodified'],
            'etag'         => '"' . $row['etag'] . '"',
            'calendarid'   => $row['calendarid'],
            'size'         => (int)$row['size'],
            'calendardata' => $row['calendardata'],
         );

    }


    /**
     * Creates a new calendar object.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function createCalendarObject($calendarId,$objectUri,$calendarData) {

        $etag = md5($calendarData);

        $stmt = $this->pdo->prepare('INSERT INTO '.$this->calendarObjectTableName.' (calendarid, uri, calendardata, lastmodified, etag, size) VALUES (?,?,?,?,?,?)');
        $stmt->execute(array($calendarId,$objectUri,$calendarData,time(), $etag, strlen($calendarData)));
        $stmt = $this->pdo->prepare('UPDATE '.$this->calendarTableName.' SET ctag = ctag + 1 WHERE id = ?');
        $stmt->execute(array($calendarId));

        return '"' . $etag . '"';

    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function updateCalendarObject($calendarId,$objectUri,$calendarData) {

        $etag = md5($calendarData);

        $stmt = $this->pdo->prepare('UPDATE '.$this->calendarObjectTableName.' SET calendardata = ?, lastmodified = ?, etag = ?, size = ? WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarData,time(),$calendarId,$objectUri, $etag, strlen($calendarData)));
        $stmt = $this->pdo->prepare('UPDATE '.$this->calendarTableName.' SET ctag = ctag + 1 WHERE id = ?');
        $stmt->execute(array($calendarId));

        return '"' . $etag. '"';

    }

    /**
     * Deletes an existing calendar object.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @return void
     */
    public function deleteCalendarObject($calendarId,$objectUri) {

        $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarObjectTableName.' WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId,$objectUri));
        $stmt = $this->pdo->prepare('UPDATE '. $this->calendarTableName .' SET ctag = ctag + 1 WHERE id = ?');
        $stmt->execute(array($calendarId));

    }

}
