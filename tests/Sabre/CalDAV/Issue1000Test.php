<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\HTTP;

/**
 * This unittest for https://github.com/sabre-io/dav/issues/1000.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Issue1000Test extends \Sabre\AbstractDAVServerTestCase
{
    protected $setupCalDAV = true;

    protected $caldavCalendars = [
        [
            'id' => 1,
            'name' => 'Calendar',
            'principaluri' => 'principals/user1',
            'uri' => 'calendar1',
        ],
    ];

    protected $caldavCalendarObjects = [
        1 => [
            'event1.ics' => [
                'calendardata' => 'BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:1111
DTSTAMP:20120418T152519Z
DTSTART;VALUE=DATE:20120330
DTEND;VALUE=DATE:20120531
SEQUENCE:1
SUMMARY:Birthday1
TRANSP:TRANSPARENT
BEGIN:VALARM
ACTION:EMAIL
ATTENDEE:MAILTO:xxx@domain.de
TRIGGER;VALUE=DATE-TIME:20120329T060000Z
END:VALARM
END:VEVENT
END:VCALENDAR
',
            ],
            'event2.ics' => [
                'calendardata' => 'BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:1234
DTSTAMP:20120418T152519Z
DTSTART;VALUE=DATE:20120330
DTEND;VALUE=DATE:20120531
SEQUENCE:1
SUMMARY:Birthday2
TRANSP:TRANSPARENT
END:VEVENT
END:VCALENDAR
',
            ],
        ],
    ];

    public function testIssue211()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_CONTENT_TYPE' => 'application/xml',
            'REQUEST_URI' => '/calendars/user1/calendar1',
            'HTTP_DEPTH' => '1',
        ]);

        $request->setBody('<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
    <D:prop>
        <D:getetag/>
    </D:prop>
    <C:filter>
        <C:comp-filter name="VCALENDAR">
            <C:comp-filter name="VEVENT">
                <C:comp-filter name="VALARM">
                    <C:is-not-defined/>
                </C:comp-filter>
            </C:comp-filter>
        </C:comp-filter>
    </C:filter>
</C:calendar-query>');

        $response = $this->request($request);

        self::assertTrue(strpos($response->getBodyAsString(), 'event2.ics') > 0);
        self::assertTrue(strpos($response->getBodyAsString(), 'event1.ics') === false);
    }
}
