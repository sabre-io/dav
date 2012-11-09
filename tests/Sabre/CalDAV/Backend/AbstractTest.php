<?php

namespace Sabre\CalDAV\Backend;

class AbstractTest extends \PHPUnit_Framework_TestCase {

    function testUpdateCalendar() {

        $abstract = new AbstractMock();
        $this->assertEquals(false, $abstract->updateCalendar('randomid', array('{DAV:}displayname' => 'anything')));

    }

    function testCalendarQuery() {

        $abstract = new AbstractMock();
        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => null,
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
            'event1.ics',
        ), $abstract->calendarQuery(1, $filters));

    }

}

class AbstractMock extends AbstractBackend {

    function getCalendarsForUser($principalUri) { }
    function createCalendar($principalUri,$calendarUri,array $properties) { }
    function deleteCalendar($calendarId) { }
    function getCalendarObjects($calendarId) { 

        return array(
            array(
                'id' => 1,
                'calendarid' => 1,
                'uri' => 'event1.ics',
            ),
            array(
                'id' => 2,
                'calendarid' => 1,
                'uri' => 'task1.ics',
            ),
        );

    }
    function getCalendarObject($calendarId,$objectUri) { 

        switch($objectUri) {

            case 'event1.ics' :
                return array(
                    'id' => 1,
                    'calendarid' => 1,
                    'uri' => 'event1.ics',
                    'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
                );
            case 'task1.ics' :
                return array(
                    'id' => 1,
                    'calendarid' => 1,
                    'uri' => 'event1.ics',
                    'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n",
                );

        }

    }
    function createCalendarObject($calendarId,$objectUri,$calendarData) { }
    function updateCalendarObject($calendarId,$objectUri,$calendarData) { }
    function deleteCalendarObject($calendarId,$objectUri) { }

}
