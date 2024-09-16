<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

class CalendarHomeNotificationsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetChildrenNoSupport()
    {
        $backend = new Backend\Mock();
        $calendarHome = new CalendarHome($backend, ['uri' => 'principals/user']);

        self::assertEquals(
            [],
            $calendarHome->getChildren()
        );
    }

    public function testGetChildNoSupport()
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);
        $backend = new Backend\Mock();
        $calendarHome = new CalendarHome($backend, ['uri' => 'principals/user']);
        $calendarHome->getChild('notifications');
    }

    public function testGetChildren()
    {
        $backend = new Backend\MockSharing();
        $calendarHome = new CalendarHome($backend, ['uri' => 'principals/user']);

        $result = $calendarHome->getChildren();
        self::assertEquals('notifications', $result[0]->getName());
    }

    public function testGetChild()
    {
        $backend = new Backend\MockSharing();
        $calendarHome = new CalendarHome($backend, ['uri' => 'principals/user']);
        $result = $calendarHome->getChild('notifications');
        self::assertEquals('notifications', $result->getName());
    }
}
