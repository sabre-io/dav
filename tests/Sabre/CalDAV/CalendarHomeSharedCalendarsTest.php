<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAV;

class CalendarHomeSharedCalendarsTest extends \PHPUnit\Framework\TestCase
{
    protected $backend;

    public function getInstance()
    {
        $calendars = [
            [
                'id' => 1,
                'principaluri' => 'principals/user1',
            ],
            [
                'id' => 2,
                '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/cal1',
                '{http://sabredav.org/ns}owner-principal' => 'principal/owner',
                '{http://sabredav.org/ns}read-only' => false,
                'principaluri' => 'principals/user1',
            ],
        ];

        $this->backend = new Backend\MockSharing(
            $calendars,
            [],
            []
        );

        return new CalendarHome($this->backend, [
            'uri' => 'principals/user1',
        ]);
    }

    public function testSimple()
    {
        $instance = $this->getInstance();
        self::assertEquals('user1', $instance->getName());
    }

    public function testGetChildren()
    {
        $instance = $this->getInstance();
        $children = $instance->getChildren();
        self::assertEquals(3, count($children));

        // Testing if we got all the objects back.
        $sharedCalendars = 0;
        $hasOutbox = false;
        $hasNotifications = false;

        foreach ($children as $child) {
            if ($child instanceof ISharedCalendar) {
                ++$sharedCalendars;
            }
            if ($child instanceof Notifications\ICollection) {
                $hasNotifications = true;
            }
        }
        self::assertEquals(2, $sharedCalendars);
        self::assertTrue($hasNotifications);
    }

    public function testShareReply()
    {
        $instance = $this->getInstance();
        $result = $instance->shareReply('uri', DAV\Sharing\Plugin::INVITE_DECLINED, 'curi', '1');
        self::assertNull($result);
    }
}
