<?php

namespace Sabre\CalDAV;
use Sabre\HTTP;
use Sabre\VObject;

/**
 * This unittest was created to test a bug reported in Baikal.
 *
 * https://github.com/netgusto/Baikal/issues/360
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Baikal360Test extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;

    protected $caldavCalendars = array(
        array(
            'id' => 1,
            'name' => 'Calendar',
            'principaluri' => 'principals/user1',
            'uri' => 'calendar1',
        )
    );

    protected $caldavCalendarObjects = array(
        1 => array(
            'event.ics' => array(
                'calendardata' => 'BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:20150401T000644Z-3964-1000-1766-194@iMac
DTSTAMP:20150331T014436Z
DTSTART;TZID=/freeassociation.sourceforge.net/Tzfile/America/Boise:
 20150428T181500
DTEND;TZID=/freeassociation.sourceforge.net/Tzfile/America/Boise:
 20150428T204500
TRANSP:OPAQUE
SEQUENCE:3
SUMMARY:Baseball Game
LOCATION:<redacted>
CLASS:PUBLIC
CREATED:20150401T000644Z
LAST-MODIFIED:20150401T000644Z
END:VEVENT
END:VCALENDAR
',
            ),
        ),
    );

    function testIssue() {

        $request = new HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_CONTENT_TYPE' => 'text/xml',
            'REQUEST_URI' => '/calendars/user1/calendar1',
            'HTTP_DEPTH' => ' 1',
        ));

        $request->setBody('<?xml version="1.0" encoding="UTF-8"?>
<ns0:calendar-query xmlns:ns0="urn:ietf:params:xml:ns:caldav" xmlns:ns1="DAV:">
  <ns1:prop>
    <ns1:getetag />
  </ns1:prop>
  <ns0:filter>
    <ns0:comp-filter name="VCALENDAR">
      <ns0:comp-filter name="VEVENT">
        <ns0:time-range start="20141203T000000Z" end="20160302T000000Z" />
      </ns0:comp-filter>
    </ns0:comp-filter>
  </ns0:filter>
</ns0:calendar-query>');

        $response = $this->request($request);

        $body = $response->body;

        $this->assertTrue(strpos($body,'200 OK')!==false, 'We didn\'t receive a result');

    }
}
