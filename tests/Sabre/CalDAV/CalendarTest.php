<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAV\PropPatch;

class CalendarTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Sabre\CalDAV\Backend\PDO
     */
    protected $backend;
    protected $principalBackend;
    /**
     * @var Sabre\CalDAV\Calendar
     */
    protected $calendar;
    /**
     * @var array
     */
    protected $calendars;

    public function setup(): void
    {
        $this->backend = TestUtil::getBackend();

        $this->calendars = $this->backend->getCalendarsForUser('principals/user1');
        self::assertEquals(2, count($this->calendars));
        $this->calendar = new Calendar($this->backend, $this->calendars[0]);
    }

    public function teardown(): void
    {
        unset($this->backend);
    }

    public function testSimple()
    {
        self::assertEquals($this->calendars[0]['uri'], $this->calendar->getName());
    }

    /**
     * @depends testSimple
     */
    public function testUpdateProperties()
    {
        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'NewName',
        ]);

        $result = $this->calendar->propPatch($propPatch);
        $result = $propPatch->commit();

        self::assertEquals(true, $result);

        $calendars2 = $this->backend->getCalendarsForUser('principals/user1');
        self::assertEquals('NewName', $calendars2[0]['{DAV:}displayname']);
    }

    /**
     * @depends testSimple
     */
    public function testGetProperties()
    {
        $question = [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
        ];

        $result = $this->calendar->getProperties($question);

        foreach ($question as $q) {
            self::assertArrayHasKey($q, $result);
        }

        self::assertEquals(['VEVENT', 'VTODO'], $result['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set']->getValue());
    }

    /**
     * @depends testSimple
     */
    public function testGetChildNotFound()
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);
        $this->calendar->getChild('randomname');
    }

    /**
     * @depends testSimple
     */
    public function testGetChildren()
    {
        $children = $this->calendar->getChildren();
        self::assertEquals(1, count($children));

        self::assertTrue($children[0] instanceof CalendarObject);
    }

    /**
     * @depends testGetChildren
     */
    public function testChildExists()
    {
        self::assertFalse($this->calendar->childExists('foo'));

        $children = $this->calendar->getChildren();
        self::assertTrue($this->calendar->childExists($children[0]->getName()));
    }

    public function testCreateDirectory()
    {
        $this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
        $this->calendar->createDirectory('hello');
    }

    public function testSetName()
    {
        $this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
        $this->calendar->setName('hello');
    }

    public function testGetLastModified()
    {
        self::assertNull($this->calendar->getLastModified());
    }

    public function testCreateFile()
    {
        $file = fopen('php://memory', 'r+');
        fwrite($file, TestUtil::getTestCalendarData());
        rewind($file);

        $this->calendar->createFile('hello', $file);

        $file = $this->calendar->getChild('hello');
        self::assertTrue($file instanceof CalendarObject);
    }

    public function testCreateFileNoSupportedComponents()
    {
        $file = fopen('php://memory', 'r+');
        fwrite($file, TestUtil::getTestCalendarData());
        rewind($file);

        $calendar = new Calendar($this->backend, $this->calendars[1]);
        $calendar->createFile('hello', $file);

        $file = $calendar->getChild('hello');
        self::assertTrue($file instanceof CalendarObject);
    }

    public function testDelete()
    {
        $this->calendar->delete();

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        self::assertEquals(1, count($calendars));
    }

    public function testGetOwner()
    {
        self::assertEquals('principals/user1', $this->calendar->getOwner());
    }

    public function testGetGroup()
    {
        self::assertNull($this->calendar->getGroup());
    }

    public function testGetACL()
    {
        $expected = [
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{'.Plugin::NS_CALDAV.'}read-free-busy',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
        ];
        self::assertEquals($expected, $this->calendar->getACL());
    }

    public function testSetACL()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $this->calendar->setACL([]);
    }

    public function testGetSyncToken()
    {
        self::assertNull($this->calendar->getSyncToken());
    }

    public function testGetSyncTokenNoSyncSupport()
    {
        $calendar = new Calendar(new Backend\Mock([], []), []);
        self::assertNull($calendar->getSyncToken());
    }

    public function testGetChanges()
    {
        self::assertNull($this->calendar->getChanges(1, 1));
    }
}
