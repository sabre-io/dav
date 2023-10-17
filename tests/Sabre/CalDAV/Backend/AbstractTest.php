<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Backend;

use Sabre\DAV\PropPatch;

class AbstractTest extends \PHPUnit\Framework\TestCase
{
    public function testUpdateCalendar()
    {
        $abstract = new AbstractMock();
        $propPatch = new PropPatch(['{DAV:}displayname' => 'anything']);

        $abstract->updateCalendar('randomid', $propPatch);
        $result = $propPatch->commit();

        self::assertFalse($result);
    }

    public function testCalendarQuery()
    {
        $abstract = new AbstractMock();
        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name' => 'VEVENT',
                    'comp-filters' => [],
                    'prop-filters' => [],
                    'is-not-defined' => false,
                    'time-range' => null,
                ],
            ],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => null,
        ];

        self::assertEquals([
            'event1.ics',
        ], $abstract->calendarQuery(1, $filters));
    }

    public function testGetCalendarObjectByUID()
    {
        $abstract = new AbstractMock();
        self::assertNull(
            $abstract->getCalendarObjectByUID('principal1', 'zim')
        );
        self::assertEquals(
            'cal1/event1.ics',
            $abstract->getCalendarObjectByUID('principal1', 'foo')
        );
        self::assertNull(
            $abstract->getCalendarObjectByUID('principal3', 'foo')
        );
        self::assertNull(
            $abstract->getCalendarObjectByUID('principal1', 'shared')
        );
    }

    public function testGetMultipleCalendarObjects()
    {
        $abstract = new AbstractMock();
        $result = $abstract->getMultipleCalendarObjects(1, [
            'event1.ics',
            'task1.ics',
        ]);

        $expected = [
            [
                'id' => 1,
                'calendarid' => 1,
                'uri' => 'event1.ics',
                'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
            ],
            [
                'id' => 2,
                'calendarid' => 1,
                'uri' => 'task1.ics',
                'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n",
            ],
        ];

        self::assertEquals($expected, $result);
    }
}

class AbstractMock extends AbstractBackend
{
    public function getCalendarsForUser($principalUri)
    {
        return [
            [
                'id' => 1,
                'principaluri' => 'principal1',
                'uri' => 'cal1',
            ],
            [
                'id' => 2,
                'principaluri' => 'principal1',
                '{http://sabredav.org/ns}owner-principal' => 'principal2',
                'uri' => 'cal1',
            ],
        ];
    }

    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
    }

    public function deleteCalendar($calendarId)
    {
    }

    public function getCalendarObjects($calendarId)
    {
        switch ($calendarId) {
            case 1:
                return [
                    [
                        'id' => 1,
                        'calendarid' => 1,
                        'uri' => 'event1.ics',
                    ],
                    [
                        'id' => 2,
                        'calendarid' => 1,
                        'uri' => 'task1.ics',
                    ],
                ];
            case 2:
                return [
                    [
                        'id' => 3,
                        'calendarid' => 2,
                        'uri' => 'shared-event.ics',
                    ],
                ];
        }

        return [];
    }

    public function getCalendarObject($calendarId, $objectUri)
    {
        switch ($objectUri) {
            case 'event1.ics':
                return [
                    'id' => 1,
                    'calendarid' => 1,
                    'uri' => 'event1.ics',
                    'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
                ];
            case 'task1.ics':
                return [
                    'id' => 2,
                    'calendarid' => 1,
                    'uri' => 'task1.ics',
                    'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n",
                ];
            case 'shared-event.ics':
                return [
                    'id' => 3,
                    'calendarid' => 2,
                    'uri' => 'event1.ics',
                    'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:shared\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
                ];
        }
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
    }

    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
    }

    public function deleteCalendarObject($calendarId, $objectUri)
    {
    }
}
