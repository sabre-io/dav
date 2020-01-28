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

    public function setup()
    {
        $this->backend = TestUtil::getBackend();

        $this->calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(2, count($this->calendars));
        $this->calendar = new Calendar($this->backend, $this->calendars[0]);
    }

    public function teardown()
    {
        unset($this->backend);
    }

    public function testSimple()
    {
        $this->assertEquals($this->calendars[0]['uri'], $this->calendar->getName());
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

        $this->assertEquals(true, $result);

        $calendars2 = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals('NewName', $calendars2[0]['{DAV:}displayname']);
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
            $this->assertArrayHasKey($q, $result);
        }

        $this->assertEquals(['VEVENT', 'VTODO'], $result['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set']->getValue());
    }

    /**
     * @expectedException \Sabre\DAV\Exception\NotFound
     * @depends testSimple
     */
    public function testGetChildNotFound()
    {
        $this->calendar->getChild('randomname');
    }

    /**
     * @depends testSimple
     */
    public function testGetChildren()
    {
        $children = $this->calendar->getChildren();
        $this->assertEquals(1, count($children));

        $this->assertTrue($children[0] instanceof CalendarObject);
    }

    /**
     * @depends testGetChildren
     */
    public function testChildExists()
    {
        $this->assertFalse($this->calendar->childExists('foo'));

        $children = $this->calendar->getChildren();
        $this->assertTrue($this->calendar->childExists($children[0]->getName()));
    }

    /**
     * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
     */
    public function testCreateDirectory()
    {
        $this->calendar->createDirectory('hello');
    }

    /**
     * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
     */
    public function testSetName()
    {
        $this->calendar->setName('hello');
    }

    public function testGetLastModified()
    {
        $this->assertNull($this->calendar->getLastModified());
    }

    public function testCreateFile()
    {
        $file = fopen('php://memory', 'r+');
        fwrite($file, TestUtil::getTestCalendarData());
        rewind($file);

        $this->calendar->createFile('hello', $file);

        $file = $this->calendar->getChild('hello');
        $this->assertTrue($file instanceof CalendarObject);
    }

    public function testCreateFileNoSupportedComponents()
    {
        $file = fopen('php://memory', 'r+');
        fwrite($file, TestUtil::getTestCalendarData());
        rewind($file);

        $calendar = new Calendar($this->backend, $this->calendars[1]);
        $calendar->createFile('hello', $file);

        $file = $calendar->getChild('hello');
        $this->assertTrue($file instanceof CalendarObject);
    }

    public function testDelete()
    {
        $this->calendar->delete();

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1, count($calendars));
    }

    public function testGetOwner()
    {
        $this->assertEquals('principals/user1', $this->calendar->getOwner());
    }

    public function testGetGroup()
    {
        $this->assertNull($this->calendar->getGroup());
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
        $this->assertEquals($expected, $this->calendar->getACL());
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testSetACL()
    {
        $this->calendar->setACL([]);
    }

    public function testGetSyncToken()
    {
        $this->assertNull($this->calendar->getSyncToken());
    }

    public function testGetSyncTokenNoSyncSupport()
    {
        $calendar = new Calendar(new Backend\Mock([], []), []);
        $this->assertNull($calendar->getSyncToken());
    }

    public function testGetChanges()
    {
        $this->assertNull($this->calendar->getChanges(1, 1));
    }
}
