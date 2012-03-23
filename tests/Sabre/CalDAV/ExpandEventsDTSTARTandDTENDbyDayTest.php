<?php

/**
 * This unittests is created to find out why recurring events have wrong DTSTART value
 *
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_ExpandEventsDTSTARTandDTENDbyDayTest extends Sabre_DAVServerTest {

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
UID:foobar
DTEND;TZID=Europe/Berlin:20120207T191500
RRULE:FREQ=WEEKLY;INTERVAL=1;BYDAY=TU,TH
SUMMARY:RecurringEvents on tuesday and thursday
DTSTART;TZID=Europe/Berlin:20120207T181500
END:VEVENT
END:VCALENDAR
',
            ),
        ),
    );

    function testExpandRecurringByDayEvent() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_CONTENT_TYPE' => 'application/xml',
            'REQUEST_URI' => '/calendars/user1/calendar1',
            'HTTP_DEPTH' => '1',
        ));

        $request->setBody('<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
    <D:prop>
        <C:calendar-data>
            <C:expand start="20120210T230000Z" end="20120217T225959Z"/>
        </C:calendar-data>
        <D:getetag/>
    </D:prop>
    <C:filter>
        <C:comp-filter name="VCALENDAR">
            <C:comp-filter name="VEVENT">
                <C:time-range start="20120210T230000Z" end="20120217T225959Z"/>
            </C:comp-filter>
        </C:comp-filter>
    </C:filter>
</C:calendar-query>');

        $response = $this->request($request);

        // Everts super awesome xml parser.
        $body = substr(
            $response->body,
            $start = strpos($response->body, 'BEGIN:VCALENDAR'),
            strpos($response->body, 'END:VCALENDAR') - $start + 13
        );
        $body = str_replace('&#13;','',$body);

        $vObject = Sabre_VObject_Reader::read($body);

        $this->assertEquals(2, count($vObject->VEVENT));

        // check if DTSTARTs and DTENDs are correct
        foreach ($vObject->VEVENT as $vevent) {
            /** @var $vevent Sabre_VObject_Component_VEvent */
            foreach ($vevent->children as $child) {
                /** @var $child Sabre_VObject_Property */

                if ($child->name == 'DTSTART') {
                    // DTSTART has to be one of two valid values
                    $this->assertContains($child->value, array('20120214T171500Z', '20120216T171500Z'), 'DTSTART is not a valid value: '.$child->value);
                } elseif ($child->name == 'DTEND') {
                    // DTEND has to be one of two valid values
                    $this->assertContains($child->value, array('20120214T181500Z', '20120216T181500Z'), 'DTEND is not a valid value: '.$child->value);
                }
            }
        }
    }

}

