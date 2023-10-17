<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;

class OutboxTest extends \PHPUnit\Framework\TestCase
{
    public function testSetup()
    {
        $outbox = new Outbox('principals/user1');
        self::assertEquals('outbox', $outbox->getName());
        self::assertEquals([], $outbox->getChildren());
        self::assertEquals('principals/user1', $outbox->getOwner());
        self::assertEquals(null, $outbox->getGroup());

        self::assertEquals([
            [
                'privilege' => '{'.CalDAV\Plugin::NS_CALDAV.'}schedule-send',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{'.CalDAV\Plugin::NS_CALDAV.'}schedule-send',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
        ], $outbox->getACL());
    }
}
