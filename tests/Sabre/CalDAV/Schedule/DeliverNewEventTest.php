<?php

namespace Sabre\CalDAV\Schedule;

use
    Sabre\HTTP\Request;

class DeliverNewEventTest extends \Sabre\DAVServerTest {

    public $setupCalDAV = true;
    public $setupCalDAVScheduling = true;

    function setUp() {

        parent::setUp();
        $this->caldavBackend->createCalendar(
            'principals/user1',
            'default',
            [
               
            ]
        );

    }

    function testDelivery() {

        $request = new Request('PUT', '/calendars/user1/default/foo.ics');
        $request->setBody(<<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//Mac OS X 10.9.1//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
CREATED:20140109T204404Z
UID:AADC6438-18CF-4B52-8DD2-EF9AD75ADE83
DTEND;TZID=America/Toronto:20140107T110000
TRANSP:OPAQUE
ATTENDEE;CN="Adminstrator";CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED:mailto:user1.sabredav@sabredav.org
ATTENDEE;CN="Roxy Kesh";CUTYPE=INDIVIDUAL;EMAIL="roxannakesh@gmail.com";
 PARTSTAT=NEEDS-ACTION;ROLE=REQ-PARTICIPANT;RSVP=TRUE:mailto:roxannakesh@
 gmail.com
SUMMARY:Just testing!
DTSTART;TZID=America/Toronto:20140107T100000
DTSTAMP:20140109T204422Z
ORGANIZER;CN="Adminstrator":mailto:evertpot@gmail.com
SEQUENCE:4
END:VEVENT
END:VCALENDAR
ICS
);
        $response = $this->request($request);
        $this->assertEquals(201, $response->getStatus(), 'Incorrect status code received. Response body:' . $response->getBodyAsString());
        $this->markTestIncomplete('Need to test if the message gets delivered');

    }

}
