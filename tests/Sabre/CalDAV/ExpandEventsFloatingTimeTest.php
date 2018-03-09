<?php declare (strict_types=1);

namespace Sabre\CalDAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\VObject;

/**
 * This unittest is created to check if expand() works correctly with
 * floating times (using calendar-timezone information).
 */
class ExpandEventsFloatingTimeTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;

    protected $setupCalDAVICSExport = true;

    protected $caldavCalendars = [
        [
            'id'                                               => 1,
            'name'                                             => 'Calendar',
            'principaluri'                                     => 'principals/user1',
            'uri'                                              => 'calendar1',
            '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => 'BEGIN:VCALENDAR
VERSION:2.0
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
DTSTART:19810329T020000
TZNAME:GMT+2
TZOFFSETTO:+0200
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
DTSTART:19961027T030000
TZNAME:GMT+1
TZOFFSETTO:+0100
END:STANDARD
END:VTIMEZONE
END:VCALENDAR',
        ]
    ];

    protected $caldavCalendarObjects = [
        1 => [
           'event.ics' => [
                'calendardata' => 'BEGIN:VCALENDAR
VERSION:2.0
CALSCALE:GREGORIAN
BEGIN:VEVENT
CREATED:20140701T143658Z
UID:dba46fe8-1631-4d98-a575-97963c364dfe
DTEND:20141108T073000
TRANSP:OPAQUE
SUMMARY:Floating Time event, starting 05:30am Europe/Berlin
DTSTART:20141108T053000
DTSTAMP:20140701T143706Z
SEQUENCE:1
END:VEVENT
END:VCALENDAR
',
            ],
        ],
    ];

    function testExpandCalendarQuery() {

        $request = new ServerRequest('REPORT', '/calendars/user1/calendar1', [
            'Depth'        => 1,
            'Content-Type' => 'application/xml',
        ],'<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
    <D:prop>
        <C:calendar-data>
            <C:expand start="20141107T230000Z" end="20141108T225959Z"/>
        </C:calendar-data>
        <D:getetag/>
    </D:prop>
    <C:filter>
        <C:comp-filter name="VCALENDAR">
            <C:comp-filter name="VEVENT">
                <C:time-range start="20141107T230000Z" end="20141108T225959Z"/>
            </C:comp-filter>
        </C:comp-filter>
    </C:filter>
</C:calendar-query>');

        $response = $this->request($request);
        $responseBody = $response->getBody()->getContents();
        // Everts super awesome xml parser.
        $body = substr(
            $responseBody,
            $start = strpos($responseBody, 'BEGIN:VCALENDAR'),
            strpos($responseBody, 'END:VCALENDAR') - $start + 13
        );
        $body = str_replace('&#13;', '', $body);

        $vObject = VObject\Reader::read($body);

        // check if DTSTARTs and DTENDs are correct
        foreach ($vObject->VEVENT as $vevent) {
            /** @var $vevent VObject\Component\VEvent */
            foreach ($vevent->children() as $child) {
                /** @var $child VObject\Property */
                if ($child->name == 'DTSTART') {
                    // DTSTART should be the UTC equivalent of given floating time
                    $this->assertEquals('20141108T043000Z', $child->getValue());
                } elseif ($child->name == 'DTEND') {
                    // DTEND should be the UTC equivalent of given floating time
                    $this->assertEquals('20141108T063000Z', $child->getValue());
                }
            }
        }
    }

    function testExpandMultiGet() {

        $request = new ServerRequest('REPORT', '/calendars/user1/calendar1', [
            'Depth'        => 1,
            'Content-Type' => 'application/xml',
        ],'<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-multiget xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
    <D:prop>
        <C:calendar-data>
            <C:expand start="20141107T230000Z" end="20141108T225959Z"/>
        </C:calendar-data>
        <D:getetag/>
    </D:prop>
    <D:href>/calendars/user1/calendar1/event.ics</D:href>
</C:calendar-multiget>');

        $response = $this->request($request);

        $this->assertEquals(207, $response->getStatusCode());

        $responseBody = $response->getBody()->getContents();
        // Everts super awesome xml parser.
        $body = substr(
            $responseBody,
            $start = strpos($responseBody, 'BEGIN:VCALENDAR'),
            strpos($responseBody, 'END:VCALENDAR') - $start + 13
        );
        $body = str_replace('&#13;', '', $body);

        $vObject = VObject\Reader::read($body);

        // check if DTSTARTs and DTENDs are correct
        foreach ($vObject->VEVENT as $vevent) {
            /** @var $vevent Sabre\VObject\Component\VEvent */
            foreach ($vevent->children() as $child) {
                /** @var $child Sabre\VObject\Property */
                if ($child->name == 'DTSTART') {
                    // DTSTART should be the UTC equivalent of given floating time
                    $this->assertEquals($child->getValue(), '20141108T043000Z');
                } elseif ($child->name == 'DTEND') {
                    // DTEND should be the UTC equivalent of given floating time
                    $this->assertEquals($child->getValue(), '20141108T063000Z');
                }
            }
        }
    }

    function testExpandExport() {

        $request = (new ServerRequest('GET', '/calendars/user1/calendar1?export&start=1&end=2000000000&expand=1', [
            'Depth'        => 1,
            'Content-Type' => 'application/xml',
        ]))->withQueryParams([
            'export' => '',
            'start' => '1',
            'end' => '2000000000',
            'expand' => '1'
        ]);

        $response = $this->request($request, 200);

        $responseBody = $response->getBody()->getContents();
        // Everts super awesome xml parser.
        $body = substr(
            $responseBody,
            $start = strpos($responseBody, 'BEGIN:VCALENDAR'),
            strpos($responseBody, 'END:VCALENDAR') - $start + 13
        );
        $body = str_replace('&#13;', '', $body);

        $vObject = VObject\Reader::read($body);

        // check if DTSTARTs and DTENDs are correct
        foreach ($vObject->VEVENT as $vevent) {
            /** @var $vevent VObject\Component\VEvent */
            foreach ($vevent->children() as $child) {
                /** @var $child VObject\Property */
                if ($child->name == 'DTSTART') {
                    // DTSTART should be the UTC equivalent of given floating time
                    $this->assertEquals('20141108T043000Z', $child->getValue());
                } elseif ($child->name == 'DTEND') {
                    // DTEND should be the UTC equivalent of given floating time
                    $this->assertEquals('20141108T063000Z', $child->getValue());
                }
            }
        }
    }

}
