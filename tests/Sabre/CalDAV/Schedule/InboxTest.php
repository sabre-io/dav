<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;

class InboxTest extends \PHPUnit\Framework\TestCase
{
    public function testSetup()
    {
        $inbox = new Inbox(
            new CalDAV\Backend\MockScheduling(),
            'principals/user1'
        );
        self::assertEquals('inbox', $inbox->getName());
        self::assertEquals([], $inbox->getChildren());
        self::assertEquals('principals/user1', $inbox->getOwner());
        self::assertEquals(null, $inbox->getGroup());

        self::assertEquals([
            [
                'privilege' => '{DAV:}read',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write-properties',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}unbind',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}unbind',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-deliver',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ], $inbox->getACL());

        $ok = false;
    }

    /**
     * @depends testSetup
     */
    public function testGetChildren()
    {
        $backend = new CalDAV\Backend\MockScheduling();
        $inbox = new Inbox(
            $backend,
            'principals/user1'
        );

        self::assertEquals(
            0,
            count($inbox->getChildren())
        );
        $backend->createSchedulingObject('principals/user1', 'schedule1.ics', "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        self::assertEquals(
            1,
            count($inbox->getChildren())
        );
        self::assertInstanceOf('Sabre\CalDAV\Schedule\SchedulingObject', $inbox->getChildren()[0]);
        self::assertEquals(
            'schedule1.ics',
            $inbox->getChildren()[0]->getName()
        );
    }

    /**
     * @depends testGetChildren
     */
    public function testCreateFile()
    {
        $backend = new CalDAV\Backend\MockScheduling();
        $inbox = new Inbox(
            $backend,
            'principals/user1'
        );

        self::assertEquals(
            0,
            count($inbox->getChildren())
        );
        $inbox->createFile('schedule1.ics', "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        self::assertEquals(
            1,
            count($inbox->getChildren())
        );
        self::assertInstanceOf('Sabre\CalDAV\Schedule\SchedulingObject', $inbox->getChildren()[0]);
        self::assertEquals(
            'schedule1.ics',
            $inbox->getChildren()[0]->getName()
        );
    }

    /**
     * @depends testSetup
     */
    public function testCalendarQuery()
    {
        $backend = new CalDAV\Backend\MockScheduling();
        $inbox = new Inbox(
            $backend,
            'principals/user1'
        );

        self::assertEquals(
            0,
            count($inbox->getChildren())
        );
        $backend->createSchedulingObject('principals/user1', 'schedule1.ics', "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        self::assertEquals(
            ['schedule1.ics'],
            $inbox->calendarQuery([
                'name' => 'VCALENDAR',
                'comp-filters' => [],
                'prop-filters' => [],
                'is-not-defined' => false,
            ])
        );
    }
}
