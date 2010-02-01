<?php

class Sabre_CalDAV_Backend_PDO extends Sabre_CalDAV_Backend_Abstract {

    private $pdo;

    function __construct(PDO $pdo) {

        $this->pdo = $pdo;

    }

    function getCalendarsForUser($userId) {

        $stmt = $this->pdo->query("SELECT * FROM calendars WHERE userId = " . $this->pdo->quote($userId));
        return $stmt->fetchAll();         

    }

    function createCalendar($username,$uri,$displayName,$description) {

        $stmt = $this->pdo->prepare("INSERT INTO calendars (userid,uri,displayname,description) VALUES (?,?,?,?)");
        $stmt->execute(array($username,$uri,$displayName,$description));

    }

    function updateCalendar($calendarId,$displayName,$description) {

        $stmt = $this->pdo->prepare('UPDATE calendars SET displayname = ?, description = ? WHERE id = ?');
        $stmt->execute(array($displayName,$description,$calendarId));

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
