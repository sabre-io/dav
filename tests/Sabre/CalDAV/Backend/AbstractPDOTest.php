<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Element\Sharee;

abstract class AbstractPDOTest extends \PHPUnit\Framework\TestCase
{
    use DAV\DbTestHelperTrait;

    protected $pdo;

    public function setUp()
    {
        $this->dropTables([
            'calendarobjects',
            'calendars',
            'calendarinstances',
            'calendarchanges',
            'calendarsubscriptions',
            'schedulingobjects',
        ]);
        $this->createSchema('calendars');

        $this->pdo = $this->getDb();
    }

    public function testConstruct()
    {
        $backend = new PDO($this->pdo);
        $this->assertTrue($backend instanceof PDO);
    }

    /**
     * @depends testConstruct
     */
    public function testGetCalendarsForUserNoCalendars()
    {
        $backend = new PDO($this->pdo);
        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals([], $calendars);
    }

    /**
     * @depends testConstruct
     */
    public function testCreateCalendarAndFetch()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
            '{DAV:}displayname' => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
        ]);
        $calendars = $backend->getCalendarsForUser('principals/user2');

        $elementCheck = [
            'uri' => 'somerandomid',
            '{DAV:}displayname' => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
            'share-access' => \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER,
        ];

        $this->assertInternalType('array', $calendars);
        $this->assertEquals(1, count($calendars));

        foreach ($elementCheck as $name => $value) {
            $this->assertArrayHasKey($name, $calendars[0]);
            $this->assertEquals($value, $calendars[0][$name]);
        }
    }

    /**
     * @depends testConstruct
     */
    public function testUpdateCalendarAndFetch()
    {
        $backend = new PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'myCalendar',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
        ]);

        // Updating the calendar
        $backend->updateCalendar($newId, $propPatch);
        $result = $propPatch->commit();

        // Verifying the result of the update
        $this->assertTrue($result);

        // Fetching all calendars from this user
        $calendars = $backend->getCalendarsForUser('principals/user2');

        // Checking if all the information is still correct
        $elementCheck = [
            'id' => $newId,
            'uri' => 'somerandomid',
            '{DAV:}displayname' => 'myCalendar',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
            '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => '',
            '{http://calendarserver.org/ns/}getctag' => 'http://sabre.io/ns/sync/2',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
        ];

        $this->assertInternalType('array', $calendars);
        $this->assertEquals(1, count($calendars));

        foreach ($elementCheck as $name => $value) {
            $this->assertArrayHasKey($name, $calendars[0]);
            $this->assertEquals($value, $calendars[0][$name]);
        }
    }

    /**
     * @depends testConstruct
     * @expectedException \InvalidArgumentException
     */
    public function testUpdateCalendarBadId()
    {
        $backend = new PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'myCalendar',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
        ]);

        // Updating the calendar
        $backend->updateCalendar('raaaa', $propPatch);
    }

    /**
     * @depends testUpdateCalendarAndFetch
     */
    public function testUpdateCalendarUnknownProperty()
    {
        $backend = new PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'myCalendar',
            '{DAV:}yourmom' => 'wittycomment',
        ]);

        // Updating the calendar
        $backend->updateCalendar($newId, $propPatch);
        $propPatch->commit();

        // Verifying the result of the update
        $this->assertEquals([
            '{DAV:}yourmom' => 403,
            '{DAV:}displayname' => 424,
        ], $propPatch->getResult());
    }

    /**
     * @depends testCreateCalendarAndFetch
     */
    public function testDeleteCalendar()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
            '{DAV:}displayname' => 'Hello!',
        ]);

        $backend->deleteCalendar($returnedId);

        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals([], $calendars);
    }

    /**
     * @depends testCreateCalendarAndFetch
     * @expectedException \InvalidArgumentException
     */
    public function testDeleteCalendarBadID()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
            '{DAV:}displayname' => 'Hello!',
        ]);

        $backend->deleteCalendar('bad-id');
    }

    /**
     * @depends testCreateCalendarAndFetch
     * @expectedException \Sabre\DAV\Exception
     */
    public function testCreateCalendarIncorrectComponentSet()
    {
        $backend = new PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2', 'somerandomid', [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => 'blabla',
        ]);
    }

    public function testCreateCalendarObject()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = \'random-id\'');

        $row = $result->fetch(\PDO::FETCH_ASSOC);
        if (is_resource($row['calendardata'])) {
            $row['calendardata'] = stream_get_contents($row['calendardata']);
        }

        $this->assertEquals([
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('20120101'),
            'lastoccurence' => strtotime('20120101') + (3600 * 24),
            'componenttype' => 'VEVENT',
        ], $row);
    }

    public function testGetMultipleObjects()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'id-1', $object);
        $backend->createCalendarObject($returnedId, 'id-2', $object);

        $check = [
            [
                'id' => 1,
                'etag' => '"'.md5($object).'"',
                'uri' => 'id-1',
                'size' => strlen($object),
                'calendardata' => $object,
                'lastmodified' => null,
            ],
            [
                'id' => 2,
                'etag' => '"'.md5($object).'"',
                'uri' => 'id-2',
                'size' => strlen($object),
                'calendardata' => $object,
                'lastmodified' => null,
            ],
        ];

        $result = $backend->getMultipleCalendarObjects($returnedId, ['id-1', 'id-2']);

        foreach ($check as $index => $props) {
            foreach ($props as $key => $expected) {
                $actual = $result[$index][$key];

                switch ($key) {
                    case 'lastmodified':
                        $this->assertInternalType('int', $actual);
                        break;
                    case 'calendardata':
                        if (is_resource($actual)) {
                            $actual = stream_get_contents($actual);
                        }
                        // no break intentional
                    default:
                        $this->assertEquals($expected, $actual);
                }
            }
        }
    }

    /**
     * @depends testGetMultipleObjects
     * @expectedException \InvalidArgumentException
     */
    public function testGetMultipleObjectsBadId()
    {
        $backend = new PDO($this->pdo);
        $backend->getMultipleCalendarObjects('bad-id', ['foo-bar']);
    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     * @depends testCreateCalendarObject
     */
    public function testCreateCalendarObjectNoComponent()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testCreateCalendarObjectDuration()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nDURATION:P2D\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = \'random-id\'');

        $row = $result->fetch(\PDO::FETCH_ASSOC);
        if (is_resource($row['calendardata'])) {
            $row['calendardata'] = stream_get_contents($row['calendardata']);
        }

        $this->assertEquals([
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('20120101'),
            'lastoccurence' => strtotime('20120101') + (3600 * 48),
            'componenttype' => 'VEVENT',
        ], $row);
    }

    /**
     * @depends testCreateCalendarObject
     * @expectedException \InvalidArgumentException
     */
    public function testCreateCalendarObjectBadId()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nDURATION:P2D\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject('bad-id', 'random-id', $object);
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testCreateCalendarObjectNoDTEND()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = \'random-id\'');
        $row = $result->fetch(\PDO::FETCH_ASSOC);
        if (is_resource($row['calendardata'])) {
            $row['calendardata'] = stream_get_contents($row['calendardata']);
        }

        $this->assertEquals([
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime('2012-01-01 10:00:00'),
            'componenttype' => 'VEVENT',
        ], $row);
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testCreateCalendarObjectWithDTEND()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nDTEND:20120101T110000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = \'random-id\'');
        $row = $result->fetch(\PDO::FETCH_ASSOC);
        if (is_resource($row['calendardata'])) {
            $row['calendardata'] = stream_get_contents($row['calendardata']);
        }

        $this->assertEquals([
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime('2012-01-01 11:00:00'),
            'componenttype' => 'VEVENT',
        ], $row);
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testCreateCalendarObjectInfiniteRecurrence()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nRRULE:FREQ=DAILY\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = \'random-id\'');
        $row = $result->fetch(\PDO::FETCH_ASSOC);
        if (is_resource($row['calendardata'])) {
            $row['calendardata'] = stream_get_contents($row['calendardata']);
        }

        $this->assertEquals([
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime(PDO::MAX_DATE),
            'componenttype' => 'VEVENT',
        ], $row);
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testCreateCalendarObjectEndingRecurrence()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nDTEND;VALUE=DATE-TIME:20120101T110000Z\r\nUID:foo\r\nRRULE:FREQ=DAILY;COUNT=1000\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = \'random-id\'');
        $row = $result->fetch(\PDO::FETCH_ASSOC);
        if (is_resource($row['calendardata'])) {
            $row['calendardata'] = stream_get_contents($row['calendardata']);
        }

        $this->assertEquals([
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime('2012-01-01 11:00:00') + (3600 * 24 * 999),
            'componenttype' => 'VEVENT',
        ], $row);
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testCreateCalendarObjectTask()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nDUE;VALUE=DATE-TIME:20120101T100000Z\r\nEND:VTODO\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = \'random-id\'');
        $row = $result->fetch(\PDO::FETCH_ASSOC);
        if (is_resource($row['calendardata'])) {
            $row['calendardata'] = stream_get_contents($row['calendardata']);
        }

        $this->assertEquals([
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => null,
            'lastoccurence' => null,
            'componenttype' => 'VTODO',
        ], $row);
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testGetCalendarObjects()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $data = $backend->getCalendarObjects($returnedId);

        $this->assertEquals(1, count($data));
        $data = $data[0];

        $this->assertEquals('random-id', $data['uri']);
        $this->assertEquals(strlen($object), $data['size']);
    }

    /**
     * @depends testGetCalendarObjects
     * @expectedException \InvalidArgumentException
     */
    public function testGetCalendarObjectsBadId()
    {
        $backend = new PDO($this->pdo);
        $backend->getCalendarObjects('bad-id');
    }

    /**
     * @depends testGetCalendarObjects
     * @expectedException \InvalidArgumentException
     */
    public function testGetCalendarObjectBadId()
    {
        $backend = new PDO($this->pdo);
        $backend->getCalendarObject('bad-id', 'foo-bar');
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testGetCalendarObjectByUID()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $this->assertNull(
            $backend->getCalendarObjectByUID('principals/user2', 'bar')
        );
        $this->assertEquals(
            'somerandomid/random-id',
            $backend->getCalendarObjectByUID('principals/user2', 'foo')
        );
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testUpdateCalendarObject()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $object2 = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20130101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->updateCalendarObject($returnedId, 'random-id', $object2);

        $data = $backend->getCalendarObject($returnedId, 'random-id');

        if (is_resource($data['calendardata'])) {
            $data['calendardata'] = stream_get_contents($data['calendardata']);
        }

        $this->assertEquals($object2, $data['calendardata']);
        $this->assertEquals('random-id', $data['uri']);
    }

    /**
     * @depends testUpdateCalendarObject
     * @expectedException \InvalidArgumentException
     */
    public function testUpdateCalendarObjectBadId()
    {
        $backend = new PDO($this->pdo);
        $backend->updateCalendarObject('bad-id', 'object-id', 'objectdata');
    }

    /**
     * @depends testCreateCalendarObject
     */
    public function testDeleteCalendarObject()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->deleteCalendarObject($returnedId, 'random-id');

        $data = $backend->getCalendarObject($returnedId, 'random-id');
        $this->assertNull($data);
    }

    /**
     * @depends testDeleteCalendarObject
     * @expectedException \InvalidArgumentException
     */
    public function testDeleteCalendarObjectBadId()
    {
        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->deleteCalendarObject('bad-id', 'random-id');
    }

    public function testCalendarQueryNoResult()
    {
        $abstract = new PDO($this->pdo);
        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name' => 'VJOURNAL',
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

        $this->assertEquals([
        ], $abstract->calendarQuery([1, 1], $filters));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @depends testCalendarQueryNoResult
     */
    public function testCalendarQueryBadId()
    {
        $abstract = new PDO($this->pdo);
        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name' => 'VJOURNAL',
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

        $abstract->calendarQuery('bad-id', $filters);
    }

    public function testCalendarQueryTodo()
    {
        $backend = new PDO($this->pdo);
        $backend->createCalendarObject([1, 1], 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject([1, 1], 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name' => 'VTODO',
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

        $this->assertEquals([
            'todo',
        ], $backend->calendarQuery([1, 1], $filters));
    }

    public function testCalendarQueryTodoNotMatch()
    {
        $backend = new PDO($this->pdo);
        $backend->createCalendarObject([1, 1], 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject([1, 1], 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name' => 'VTODO',
                    'comp-filters' => [],
                    'prop-filters' => [
                        [
                            'name' => 'summary',
                            'text-match' => null,
                            'time-range' => null,
                            'param-filters' => [],
                            'is-not-defined' => false,
                        ],
                    ],
                    'is-not-defined' => false,
                    'time-range' => null,
                ],
            ],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => null,
        ];

        $this->assertEquals([
        ], $backend->calendarQuery([1, 1], $filters));
    }

    public function testCalendarQueryNoFilter()
    {
        $backend = new PDO($this->pdo);
        $backend->createCalendarObject([1, 1], 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject([1, 1], 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => null,
        ];

        $result = $backend->calendarQuery([1, 1], $filters);
        $this->assertTrue(in_array('todo', $result));
        $this->assertTrue(in_array('event', $result));
    }

    public function testCalendarQueryTimeRange()
    {
        $backend = new PDO($this->pdo);
        $backend->createCalendarObject([1, 1], 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject([1, 1], 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject([1, 1], 'event2', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name' => 'VEVENT',
                    'comp-filters' => [],
                    'prop-filters' => [],
                    'is-not-defined' => false,
                    'time-range' => [
                        'start' => new \DateTime('20120103'),
                        'end' => new \DateTime('20120104'),
                    ],
                ],
            ],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => null,
        ];

        $this->assertEquals([
            'event2',
        ], $backend->calendarQuery([1, 1], $filters));
    }

    public function testCalendarQueryTimeRangeNoEnd()
    {
        $backend = new PDO($this->pdo);
        $backend->createCalendarObject([1, 1], 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject([1, 1], 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject([1, 1], 'event2', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name' => 'VEVENT',
                    'comp-filters' => [],
                    'prop-filters' => [],
                    'is-not-defined' => false,
                    'time-range' => [
                        'start' => new \DateTime('20120102'),
                        'end' => null,
                    ],
                ],
            ],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => null,
        ];

        $this->assertEquals([
            'event2',
        ], $backend->calendarQuery([1, 1], $filters));
    }

    public function testGetChanges()
    {
        $backend = new PDO($this->pdo);
        $id = $backend->createCalendar(
            'principals/user1',
            'bla',
            []
        );
        $result = $backend->getChangesForCalendar($id, null, 1);

        $this->assertEquals([
            'syncToken' => 1,
            'modified' => [],
            'deleted' => [],
            'added' => [],
        ], $result);

        $currentToken = $result['syncToken'];

        $dummyTodo = "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($id, 'todo1.ics', $dummyTodo);
        $backend->createCalendarObject($id, 'todo2.ics', $dummyTodo);
        $backend->createCalendarObject($id, 'todo3.ics', $dummyTodo);
        $backend->updateCalendarObject($id, 'todo1.ics', $dummyTodo);
        $backend->deleteCalendarObject($id, 'todo2.ics');

        $result = $backend->getChangesForCalendar($id, $currentToken, 1);

        $this->assertEquals([
            'syncToken' => 6,
            'modified' => ['todo1.ics'],
            'deleted' => ['todo2.ics'],
            'added' => ['todo3.ics'],
        ], $result);

        $result = $backend->getChangesForCalendar($id, null, 1);

        $this->assertEquals([
            'syncToken' => 6,
            'modified' => [],
            'deleted' => [],
            'added' => ['todo1.ics', 'todo3.ics'],
        ], $result);
    }

    /**
     * @depends testGetChanges
     * @expectedException \InvalidArgumentException
     */
    public function testGetChangesBadId()
    {
        $backend = new PDO($this->pdo);
        $id = $backend->createCalendar(
            'principals/user1',
            'bla',
            []
        );
        $backend->getChangesForCalendar('bad-id', null, 1);
    }

    public function testCreateSubscriptions()
    {
        $props = [
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/cal.ics', false),
            '{DAV:}displayname' => 'cal',
            '{http://apple.com/ns/ical/}refreshrate' => 'P1W',
            '{http://apple.com/ns/ical/}calendar-color' => '#FF00FFFF',
            '{http://calendarserver.org/ns/}subscribed-strip-todos' => true,
            //'{http://calendarserver.org/ns/}subscribed-strip-alarms' => true,
            '{http://calendarserver.org/ns/}subscribed-strip-attachments' => true,
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);

        $subs = $backend->getSubscriptionsForUser('principals/user1');

        $expected = $props;
        $expected['id'] = 1;
        $expected['uri'] = 'sub1';
        $expected['principaluri'] = 'principals/user1';

        unset($expected['{http://calendarserver.org/ns/}source']);
        $expected['source'] = 'http://example.org/cal.ics';

        $this->assertEquals(1, count($subs));
        foreach ($expected as $k => $v) {
            $this->assertEquals($subs[0][$k], $expected[$k]);
        }
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testCreateSubscriptionFail()
    {
        $props = [
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);
    }

    public function testUpdateSubscriptions()
    {
        $props = [
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/cal.ics', false),
            '{DAV:}displayname' => 'cal',
            '{http://apple.com/ns/ical/}refreshrate' => 'P1W',
            '{http://apple.com/ns/ical/}calendar-color' => '#FF00FFFF',
            '{http://calendarserver.org/ns/}subscribed-strip-todos' => true,
            //'{http://calendarserver.org/ns/}subscribed-strip-alarms' => true,
            '{http://calendarserver.org/ns/}subscribed-strip-attachments' => true,
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);

        $newProps = [
            '{DAV:}displayname' => 'new displayname',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/cal2.ics', false),
        ];

        $propPatch = new DAV\PropPatch($newProps);
        $backend->updateSubscription(1, $propPatch);
        $result = $propPatch->commit();

        $this->assertTrue($result);

        $subs = $backend->getSubscriptionsForUser('principals/user1');

        $expected = array_merge($props, $newProps);
        $expected['id'] = 1;
        $expected['uri'] = 'sub1';
        $expected['principaluri'] = 'principals/user1';

        unset($expected['{http://calendarserver.org/ns/}source']);
        $expected['source'] = 'http://example.org/cal2.ics';

        $this->assertEquals(1, count($subs));
        foreach ($expected as $k => $v) {
            $this->assertEquals($subs[0][$k], $expected[$k]);
        }
    }

    public function testUpdateSubscriptionsFail()
    {
        $props = [
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/cal.ics', false),
            '{DAV:}displayname' => 'cal',
            '{http://apple.com/ns/ical/}refreshrate' => 'P1W',
            '{http://apple.com/ns/ical/}calendar-color' => '#FF00FFFF',
            '{http://calendarserver.org/ns/}subscribed-strip-todos' => true,
            //'{http://calendarserver.org/ns/}subscribed-strip-alarms' => true,
            '{http://calendarserver.org/ns/}subscribed-strip-attachments' => true,
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'new displayname',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/cal2.ics', false),
            '{DAV:}unknown' => 'foo',
        ]);

        $backend->updateSubscription(1, $propPatch);
        $propPatch->commit();

        $this->assertEquals([
            '{DAV:}unknown' => 403,
            '{DAV:}displayname' => 424,
            '{http://calendarserver.org/ns/}source' => 424,
        ], $propPatch->getResult());
    }

    public function testDeleteSubscriptions()
    {
        $props = [
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/cal.ics', false),
            '{DAV:}displayname' => 'cal',
            '{http://apple.com/ns/ical/}refreshrate' => 'P1W',
            '{http://apple.com/ns/ical/}calendar-color' => '#FF00FFFF',
            '{http://calendarserver.org/ns/}subscribed-strip-todos' => true,
            //'{http://calendarserver.org/ns/}subscribed-strip-alarms' => true,
            '{http://calendarserver.org/ns/}subscribed-strip-attachments' => true,
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);

        $newProps = [
            '{DAV:}displayname' => 'new displayname',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/cal2.ics', false),
        ];

        $backend->deleteSubscription(1);

        $subs = $backend->getSubscriptionsForUser('principals/user1');
        $this->assertEquals(0, count($subs));
    }

    public function testSchedulingMethods()
    {
        $backend = new PDO($this->pdo);

        $calData = "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n";

        $backend->createSchedulingObject(
            'principals/user1',
            'schedule1.ics',
            $calData
        );

        $calDataResource = "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n";
        $stream = fopen('data://text/plain,'.$calData, 'r');

        $backend->createSchedulingObject(
            'principals/user1',
            'schedule1-resource.ics',
            $stream
        );

        $expected = [
            'calendardata' => $calData,
            'uri' => 'schedule1.ics',
            'etag' => '"'.md5($calData).'"',
            'size' => strlen($calData),
        ];

        $expectedResource = [
            'calendardata' => $calDataResource,
            'uri' => 'schedule1-resource.ics',
            'etag' => '"'.md5($calDataResource).'"',
            'size' => strlen($calDataResource),
        ];

        $result = $backend->getSchedulingObject('principals/user1', 'schedule1.ics');
        foreach ($expected as $k => $v) {
            $this->assertArrayHasKey($k, $result);
            if (is_resource($result[$k])) {
                $result[$k] = stream_get_contents($result[$k]);
            }
            $this->assertEquals($v, $result[$k]);
        }

        $resultResource = $backend->getSchedulingObject('principals/user1', 'schedule1-resource.ics');
        foreach ($expected as $k => $v) {
            $this->assertArrayHasKey($k, $result);
            if (is_resource($result[$k])) {
                $result[$k] = stream_get_contents($result[$k]);
            }
            $this->assertEquals($v, $result[$k]);
        }

        $backend->deleteSchedulingObject('principals/user1', 'schedule1-resource.ics');

        $results = $backend->getSchedulingObjects('principals/user1');

        $this->assertEquals(1, count($results));
        $result = $results[0];
        foreach ($expected as $k => $v) {
            if (is_resource($result[$k])) {
                $result[$k] = stream_get_contents($result[$k]);
            }
            $this->assertEquals($v, $result[$k]);
        }

        $backend->deleteSchedulingObject('principals/user1', 'schedule1.ics');
        $result = $backend->getSchedulingObject('principals/user1', 'schedule1.ics');

        $this->assertNull($result);
    }

    public function testGetInvites()
    {
        $backend = new PDO($this->pdo);

        // creating a new calendar
        $backend->createCalendar('principals/user1', 'somerandomid', []);
        $calendar = $backend->getCalendarsForUser('principals/user1')[0];

        $result = $backend->getInvites($calendar['id']);
        $expected = [
            new Sharee([
                'href' => 'principals/user1',
                'principal' => 'principals/user1',
                'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER,
                'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
            ]),
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @depends testGetInvites
     * @expectedException \InvalidArgumentException
     */
    public function testGetInvitesBadId()
    {
        $backend = new PDO($this->pdo);

        // creating a new calendar
        $backend->createCalendar('principals/user1', 'somerandomid', []);
        $calendar = $backend->getCalendarsForUser('principals/user1')[0];

        $backend->getInvites('bad-id');
    }

    /**
     * @depends testCreateCalendarAndFetch
     */
    public function testUpdateInvites()
    {
        $backend = new PDO($this->pdo);

        // creating a new calendar
        $backend->createCalendar('principals/user1', 'somerandomid', []);
        $calendar = $backend->getCalendarsForUser('principals/user1')[0];

        $ownerSharee = new Sharee([
            'href' => 'principals/user1',
            'principal' => 'principals/user1',
            'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER,
            'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
        ]);

        // Add a new invite
        $backend->updateInvites(
            $calendar['id'],
            [
                new Sharee([
                    'href' => 'mailto:user@example.org',
                    'principal' => 'principals/user2',
                    'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
                    'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
                    'properties' => ['{DAV:}displayname' => 'User 2'],
                ]),
            ]
        );

        $result = $backend->getInvites($calendar['id']);
        $expected = [
            $ownerSharee,
            new Sharee([
                'href' => 'mailto:user@example.org',
                'principal' => 'principals/user2',
                'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
                'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
                'properties' => [
                    '{DAV:}displayname' => 'User 2',
                ],
            ]),
        ];
        $this->assertEquals($expected, $result);

        // Checking calendar_instances too
        $expectedCalendar = [
            'id' => [1, 2],
            'principaluri' => 'principals/user2',
            '{http://calendarserver.org/ns/}getctag' => 'http://sabre.io/ns/sync/1',
            '{http://sabredav.org/ns}sync-token' => '1',
            'share-access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
            'read-only' => true,
            'share-resource-uri' => '/ns/share/1',
        ];
        $calendars = $backend->getCalendarsForUser('principals/user2');

        foreach ($expectedCalendar as $k => $v) {
            $this->assertEquals(
                $v,
                $calendars[0][$k],
                'Key '.$k.' in calendars array did not have the expected value.'
            );
        }

        // Updating an invite
        $backend->updateInvites(
            $calendar['id'],
            [
                new Sharee([
                    'href' => 'mailto:user@example.org',
                    'principal' => 'principals/user2',
                    'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE,
                    'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
                ]),
            ]
        );

        $result = $backend->getInvites($calendar['id']);
        $expected = [
            $ownerSharee,
            new Sharee([
                'href' => 'mailto:user@example.org',
                'principal' => 'principals/user2',
                'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE,
                'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
                'properties' => [
                    '{DAV:}displayname' => 'User 2',
                ],
            ]),
        ];
        $this->assertEquals($expected, $result);

        // Removing an invite
        $backend->updateInvites(
            $calendar['id'],
            [
                new Sharee([
                    'href' => 'mailto:user@example.org',
                    'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS,
                ]),
            ]
        );

        $result = $backend->getInvites($calendar['id']);
        $expected = [
            $ownerSharee,
        ];
        $this->assertEquals($expected, $result);

        // Preventing the owner share from being removed
        $backend->updateInvites(
            $calendar['id'],
            [
                new Sharee([
                    'href' => 'principals/user2',
                    'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS,
                ]),
            ]
        );

        $result = $backend->getInvites($calendar['id']);
        $expected = [
            new Sharee([
                'href' => 'principals/user1',
                'principal' => 'principals/user1',
                'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER,
                'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
            ]),
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @depends testUpdateInvites
     * @expectedException \InvalidArgumentException
     */
    public function testUpdateInvitesBadId()
    {
        $backend = new PDO($this->pdo);
        // Add a new invite
        $backend->updateInvites(
            'bad-id',
            []
        );
    }

    /**
     * @depends testUpdateInvites
     */
    public function testUpdateInvitesNoPrincipal()
    {
        $backend = new PDO($this->pdo);

        // creating a new calendar
        $backend->createCalendar('principals/user1', 'somerandomid', []);
        $calendar = $backend->getCalendarsForUser('principals/user1')[0];

        $ownerSharee = new Sharee([
            'href' => 'principals/user1',
            'principal' => 'principals/user1',
            'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER,
            'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
        ]);

        // Add a new invite
        $backend->updateInvites(
            $calendar['id'],
            [
                new Sharee([
                    'href' => 'mailto:user@example.org',
                    'principal' => null,
                    'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
                    'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
                    'properties' => ['{DAV:}displayname' => 'User 2'],
                ]),
            ]
        );

        $result = $backend->getInvites($calendar['id']);
        $expected = [
            $ownerSharee,
            new Sharee([
                'href' => 'mailto:user@example.org',
                'principal' => null,
                'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
                'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_INVALID,
                'properties' => [
                    '{DAV:}displayname' => 'User 2',
                ],
            ]),
        ];
        $this->assertEquals($expected, $result, '', 0.0, 10, true); // Last argument is $canonicalize = true, which allows us to compare, ignoring the order, because it's different between MySQL and Sqlite.
    }

    /**
     * @depends testUpdateInvites
     */
    public function testDeleteSharedCalendar()
    {
        $backend = new PDO($this->pdo);

        // creating a new calendar
        $backend->createCalendar('principals/user1', 'somerandomid', []);
        $calendar = $backend->getCalendarsForUser('principals/user1')[0];

        $ownerSharee = new Sharee([
            'href' => 'principals/user1',
            'principal' => 'principals/user1',
            'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER,
            'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
        ]);

        // Add a new invite
        $backend->updateInvites(
            $calendar['id'],
            [
                new Sharee([
                    'href' => 'mailto:user@example.org',
                    'principal' => 'principals/user2',
                    'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
                    'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
                    'properties' => ['{DAV:}displayname' => 'User 2'],
                ]),
            ]
        );

        $expectedCalendar = [
            'id' => [1, 2],
            'principaluri' => 'principals/user2',
            '{http://calendarserver.org/ns/}getctag' => 'http://sabre.io/ns/sync/1',
            '{http://sabredav.org/ns}sync-token' => '1',
            'share-access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
            'read-only' => true,
            'share-resource-uri' => '/ns/share/1',
        ];
        $calendars = $backend->getCalendarsForUser('principals/user2');

        foreach ($expectedCalendar as $k => $v) {
            $this->assertEquals(
                $v,
                $calendars[0][$k],
                'Key '.$k.' in calendars array did not have the expected value.'
            );
        }

        // Removing the shared calendar.
        $backend->deleteCalendar($calendars[0]['id']);

        $this->assertEquals(
            [],
            $backend->getCalendarsForUser('principals/user2')
        );

        $result = $backend->getInvites($calendar['id']);
        $expected = [
            new Sharee([
                'href' => 'principals/user1',
                'principal' => 'principals/user1',
                'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER,
                'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
            ]),
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \Sabre\DAV\Exception\NotImplemented
     */
    public function testSetPublishStatus()
    {
        $backend = new PDO($this->pdo);
        $backend->setPublishStatus([1, 1], true);
    }
}
