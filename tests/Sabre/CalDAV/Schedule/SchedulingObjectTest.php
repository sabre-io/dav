<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;
use Sabre\CalDAV\Backend;

class SchedulingObjectTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CalDAV\Backend\PDO
     */
    protected $backend;
    /**
     * @var CalDAV\Calendar
     */
    protected $calendar;
    /**
     * @var Inbox
     */
    protected $inbox;
    protected $principalBackend;

    protected $data;
    protected $data2;

    public function setup(): void
    {
        $this->backend = new Backend\MockScheduling();

        $this->data = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VEVENT
SEQUENCE:1
END:VEVENT
END:VCALENDAR
ICS;
        $this->data = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VEVENT
SEQUENCE:2
END:VEVENT
END:VCALENDAR
ICS;

        $this->inbox = new Inbox($this->backend, 'principals/user1');
        $this->inbox->createFile('item1.ics', $this->data);
    }

    public function teardown(): void
    {
        unset($this->inbox);
        unset($this->backend);
    }

    public function testSetup()
    {
        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        self::assertIsString($children[0]->getName());
        self::assertIsString($children[0]->get());
        self::assertIsString($children[0]->getETag());
        self::assertEquals('text/calendar; charset=utf-8', $children[0]->getContentType());
    }

    public function testInvalidArg1()
    {
        $this->expectException('InvalidArgumentException');
        $obj = new SchedulingObject(
            new Backend\MockScheduling([], []),
            []
        );
    }

    public function testInvalidArg2()
    {
        $this->expectException('InvalidArgumentException');
        $obj = new SchedulingObject(
            new Backend\MockScheduling([], []),
            ['calendarid' => '1']
        );
    }

    /**
     * @depends testSetup
     */
    public function testPut()
    {
        $this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        $children[0]->put('');
    }

    /**
     * @depends testSetup
     */
    public function testDelete()
    {
        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        $obj->delete();

        $children2 = $this->inbox->getChildren();
        self::assertEquals(count($children) - 1, count($children2));
    }

    /**
     * @depends testSetup
     */
    public function testGetLastModified()
    {
        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];

        $lastMod = $obj->getLastModified();
        self::assertTrue(is_int($lastMod) || ctype_digit($lastMod) || is_null($lastMod));
    }

    /**
     * @depends testSetup
     */
    public function testGetSize()
    {
        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];

        $size = $obj->getSize();
        self::assertIsInt($size);
    }

    public function testGetOwner()
    {
        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        self::assertEquals('principals/user1', $obj->getOwner());
    }

    public function testGetGroup()
    {
        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        self::assertNull($obj->getGroup());
    }

    public function testGetACL()
    {
        $expected = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ],
        ];

        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        self::assertEquals($expected, $obj->getACL());
    }

    public function testDefaultACL()
    {
        $backend = new Backend\MockScheduling([], []);
        $calendarObject = new SchedulingObject($backend, ['calendarid' => 1, 'uri' => 'foo', 'principaluri' => 'principals/user1']);
        $expected = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ],
        ];
        self::assertEquals($expected, $calendarObject->getACL());
    }

    public function testSetACL()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        $obj->setACL([]);
    }

    public function testGet()
    {
        $children = $this->inbox->getChildren();
        self::assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];

        self::assertEquals($this->data, $obj->get());
    }

    public function testGetRefetch()
    {
        $backend = new Backend\MockScheduling();
        $backend->createSchedulingObject('principals/user1', 'foo', 'foo');

        $obj = new SchedulingObject($backend, [
            'calendarid' => 1,
            'uri' => 'foo',
            'principaluri' => 'principals/user1',
        ]);

        self::assertEquals('foo', $obj->get());
    }

    public function testGetEtag1()
    {
        $objectInfo = [
            'calendardata' => 'foo',
            'uri' => 'foo',
            'etag' => 'bar',
            'calendarid' => 1,
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);

        self::assertEquals('bar', $obj->getETag());
    }

    public function testGetEtag2()
    {
        $objectInfo = [
            'calendardata' => 'foo',
            'uri' => 'foo',
            'calendarid' => 1,
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);

        self::assertEquals('"'.md5('foo').'"', $obj->getETag());
    }

    public function testGetSupportedPrivilegesSet()
    {
        $objectInfo = [
            'calendardata' => 'foo',
            'uri' => 'foo',
            'calendarid' => 1,
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        self::assertNull($obj->getSupportedPrivilegeSet());
    }

    public function testGetSize1()
    {
        $objectInfo = [
            'calendardata' => 'foo',
            'uri' => 'foo',
            'calendarid' => 1,
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        self::assertEquals(3, $obj->getSize());
    }

    public function testGetSize2()
    {
        $objectInfo = [
            'uri' => 'foo',
            'calendarid' => 1,
            'size' => 4,
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        self::assertEquals(4, $obj->getSize());
    }

    public function testGetContentType()
    {
        $objectInfo = [
            'uri' => 'foo',
            'calendarid' => 1,
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        self::assertEquals('text/calendar; charset=utf-8', $obj->getContentType());
    }

    public function testGetContentType2()
    {
        $objectInfo = [
            'uri' => 'foo',
            'calendarid' => 1,
            'component' => 'VEVENT',
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        self::assertEquals('text/calendar; charset=utf-8; component=VEVENT', $obj->getContentType());
    }

    public function testGetACL2()
    {
        $objectInfo = [
            'uri' => 'foo',
            'calendarid' => 1,
            'acl' => [],
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        self::assertEquals([], $obj->getACL());
    }
}
