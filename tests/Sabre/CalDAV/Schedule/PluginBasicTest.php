<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

class PluginBasicTest extends \Sabre\DAVServerTest
{
    public $setupCalDAV = true;
    public $setupCalDAVScheduling = true;

    public function testSimple()
    {
        $plugin = new Plugin();
        self::assertEquals(
            'caldav-schedule',
            $plugin->getPluginInfo()['name']
        );
    }

    public function testOptions()
    {
        $plugin = new Plugin();
        $expected = [
            'calendar-auto-schedule',
            'calendar-availability',
        ];
        self::assertEquals($expected, $plugin->getFeatures());
    }

    public function testGetHTTPMethods()
    {
        self::assertEquals([], $this->caldavSchedulePlugin->getHTTPMethods('notfound'));
        self::assertEquals([], $this->caldavSchedulePlugin->getHTTPMethods('calendars/user1'));
        self::assertEquals(['POST'], $this->caldavSchedulePlugin->getHTTPMethods('calendars/user1/outbox'));
    }
}
