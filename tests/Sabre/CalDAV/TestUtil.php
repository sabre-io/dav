<?php

class Sabre_CalDAV_TestUtil {

    static function getBackend() {

        $backend = new Sabre_CalDAV_Backend_PDO(self::getSQLiteDB());
        return $backend;

    }

    static function getSQLiteDB() {

        if (file_exists(SABRE_TEMPDIR . '/testdb.sqlite'))
            unlink(SABRE_TEMPDIR . '/testdb.sqlite');

        $pdo = new PDO('sqlite:' . SABRE_TEMPDIR . '/testdb.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $pdo->query('
CREATE TABLE calendarobjects ( 
	id integer primary key asc, 
    calendardata text, 
    uri text, 
    calendarid integer, 
    lastmodified integer
);
');

        $pdo->query('
CREATE TABLE calendars (
    id integer primary key asc, 
    principaluri text, 
    displayname text, 
    uri text, 
    description text,
	calendarorder integer,
    calendarcolor text	
);');

        $pdo->query('INSERT INTO calendars (principaluri,displayname,uri,description,calendarorder,calendarcolor) 
            VALUES ("principals/user1","user1 calendar","UUID-123467","Calendar description", "1", "#FF0000");');

        $pdo->query('INSERT INTO calendarobjects (calendardata, uri, calendarid, lastmodified) VALUES ("calendardata goes here", "UUID-2345", 1, DATETIME("NOW"))');

        return $pdo;

    }

}
