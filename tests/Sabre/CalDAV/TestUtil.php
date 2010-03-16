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
    ctag integer,
    description text,
	calendarorder integer,
    calendarcolor text,
    timezone text
);');

        $pdo->query('INSERT INTO calendars (principaluri,displayname,uri,description,calendarorder,calendarcolor) 
            VALUES ("principals/user1","user1 calendar","UUID-123467","Calendar description", "1", "#FF0000");');

        $stmt = $pdo->prepare('INSERT INTO calendarobjects (calendardata, uri, calendarid, lastmodified) VALUES (?, "UUID-2345", 1, DATETIME("NOW"))');
        $stmt->execute(array(self::getTestCalendarData()));

        return $pdo;

    }

    static function getTestCalendarData($type = 1) {

        $calendarData = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 4.0.1//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Asia/Seoul
BEGIN:DAYLIGHT
TZOFFSETFROM:+0900
RRULE:FREQ=YEARLY;UNTIL=19880507T150000Z;BYMONTH=5;BYDAY=2SU
DTSTART:19870510T000000
TZNAME:GMT+09:00
TZOFFSETTO:+1000
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1000
DTSTART:19881009T000000
TZNAME:GMT+09:00
TZOFFSETTO:+0900
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20100225T154229Z
UID:39A6B5ED-DD51-4AFE-A683-C35EE3749627
TRANSP:TRANSPARENT
SUMMARY:Something here
DTSTAMP:20100228T130202Z';

        switch($type) {
            case 1 :
                $calendarData.="\nDTSTART;TZID=Asia/Seoul:20100223T060000\nDTEND;TZID=Asia/Seoul:20100223T070000\n";
                break;
            case 2 :
                $calendarData.="\nDTSTART:20100223T060000\nDTEND:20100223T070000\n";
                break;
            case 3 :
                $calendarData.="\nDTSTART;VALUE=DATE:20100223\nDTEND;VALUE=DATE:20100223\n";
                break;
            case 4 :
                $calendarData.="\nDTSTART;TZID=Asia/Seoul:20100223T060000\nDURATION:PT1H\n";
                break;
            case 5 :
                $calendarData.="\nDTSTART;TZID=Asia/Seoul:20100223T060000\nDURATION:-P5D\n";
                break;
            case 6 :
                $calendarData.="\nDTSTART;VALUE=DATE:20100223\n";
                break;
            case 7 :
                $calendarData.="\nDTSTART;VALUE=DATETIME:20100223T060000\n";
                break;

            // No DTSTART, so intentionally broken
            case 'X' :
                $calendarData.="\n";
                break;
        }


        $calendarData.='ATTENDEE;PARTSTAT=NEEDS-ACTION:mailto:lisa@example.com
SEQUENCE:2
END:VEVENT
END:VCALENDAR';

        return $calendarData;

    }

}
