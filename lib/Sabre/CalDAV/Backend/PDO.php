<?php

class Sabre_CalDAV_Backend_PDO extends Sabre_CalDAV_Backend_Abstract {

    private $pdo;

    public $propertyMap = array(
        '{DAV:}displayname'                          => 'displayname',
        '{urn:ietf:params:xml:ns:caldav}description' => 'description',
        '{http://apple.com/ns/ical/}calendar-order'  => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color'  => 'calendarcolor',
    );

    function __construct(PDO $pdo) {

        $this->pdo = $pdo;

    }

    function getCalendarsForUser($principalUri) {

        $fields = array_values($this->propertyMap);
        $fields[] = 'id';
        $fields[] = 'uri';

        // Making fields a comma-delimited list 
        $fields = implode(', ', $fields);
        $stmt = $this->pdo->query("SELECT " . $fields . " FROM calendars WHERE principalUri = " . $this->pdo->quote($principalUri));
        $calendars = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $calendar = array(
                'id' => $row['id'],
                'uri' => $row['uri'],
            );

            foreach($this->propertyMap as $xmlName=>$dbName) {
                $calendar[$xmlName] = $row[$dbName];
            }

            $calendars[] = $calendar;

        }

        return $calendars;

    }

    function createCalendar($principalUri,$calendarUri, array $properties) {

        $fieldNames = array(
            'principaluri',
            'uri',
        );
        $values = array(
            ':principaluri' => $principalUri,
            ':uri'          => $calendarUri
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

    function updateCalendar($calendarId, array $mutations) {

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

        $stmt = $this->pdo->prepare("UPDATE calendars SET " . implode(', ',$valuesSql) . " WHERE id = ?");
        $values['id'] = $calendarId; 
        $stmt->execute(array_values($values));

        return $result;

    }

    function deleteCalendar($calendarId) {

        $stmt = $this->pdo->prepare('DELETE FROM calendarobjects WHERE calendarid = ?');
        $stmt->execute(array($calendarId));

        $stmt = $this->pdo->prepare('DELETE FROM calendars WHERE id = ?');
        $stmt->execute(array($calendarId));

    }

    function getCalendarObjects($calendarId) {

        $stmt = $this->pdo->prepare('SELECT * FROM calendarobjects WHERE calendarid = ?');
        $stmt->execute(array($calendarId));
        return $stmt->fetchAll();

    }

    function getCalendarObject($calendarId,$objectUri) {

        $stmt = $this->pdo->prepare('SELECT * FROM calendarobjects WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId, $objectUri));
        return $stmt->fetch();

    }

    function createCalendarObject($calendarId,$objectUri,$calendarData) {

        $stmt = $this->pdo->prepare('INSERT INTO calendarobjects (calendarid, uri, calendardata, lastmodified) VALUES (?,?,?,?)');
        $stmt->execute(array($calendarId,$objectUri,$calendarData,time()));

    }

    function updateCalendarObject($calendarId,$objectUri,$calendarData) {

        $stmt = $this->pdo->prepare('UPDATE calendarobjects SET calendardata = ?, lastmodified = ? WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarData,time(),$calendarId,$objectUri));

    }

    function deleteCalendarObject($calendarId,$objectUri) {

        $stmt = $this->pdo->prepare('DELETE FROM calendarobjects WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId,$objectUri));

    }


}
