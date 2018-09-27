<?php

namespace Sabre\CalDAV\Backend;

use Sabre\DAV;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Element\Sharee;

abstract class AbstractMongoTest extends \PHPUnit\Framework\TestCase
{
    use \Sabre\DAV\MongoTestHelperTrait;

    /**
     * @var CardDAV\Backend\Mongo
     */
    protected $backend;

    public function setUp()
    {
        $this->db = $this->getMongo();

        $this->db->drop();

        $this->backend = new Mongo($this->db);
    }

    protected function generateId()
    {
        return [(string) new \MongoDB\BSON\ObjectId(), (string) new \MongoDB\BSON\ObjectId()];
    }

    public function testConstruct()
    {
        $backend = $this->backend;
        $this->assertTrue($backend instanceof Mongo);
    }

    /**
     * @depends testConstruct
     */
    public function testGetCalendarsForUserNoCalendars()
    {
        $backend = $this->backend;
        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals([], $calendars);
    }

    /**
     * @depends testConstruct
     */
    public function testCreateCalendarAndFetch()
    {
        $backend = $this->backend;
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
            '{DAV:}displayname' => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
        ]);
        $calendars = $backend->getCalendarsForUser('principals/user2');

        $elementCheck = [
            'uri' => 'somerandomid',
            '{DAV:}displayname' => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
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
        $backend = $this->backend;

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'myCalendar',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
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
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
        ];

        $this->assertInternalType('array', $calendars);
        $this->assertEquals(1, count($calendars));

        foreach ($elementCheck as $name => $value) {
            $this->assertArrayHasKey($name, $calendars[0]);
            $this->assertEquals($value, $calendars[0][$name]);
        }
    }

    /**
     * @depends testUpdateCalendarAndFetch
     */
    public function testUpdateCalendarUnknownProperty()
    {
        $backend = $this->backend;

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
        $backend = $this->backend;
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
            '{DAV:}displayname' => 'Hello!',
        ]);

        $backend->deleteCalendar($returnedId);

        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals([], $calendars);
    }

    /**
     * @depends testCreateCalendarAndFetch
     * @expectedException \Sabre\DAV\Exception
     */
    public function testCreateCalendarIncorrectComponentSet()
    {
        $backend = $this->backend;

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2', 'somerandomid', [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => 'blabla',
        ]);
    }

    public function testGetMultipleObjects()
    {
        $backend = $this->backend;
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'id-1', $object);
        $backend->createCalendarObject($returnedId, 'id-2', $object);

        $check = [
            [
                'etag' => '"'.md5($object).'"',
                'uri' => 'id-1',
                'size' => strlen($object),
                'calendardata' => $object,
                'lastmodified' => null,
            ],
            [
                'etag' => '"'.md5($object).'"',
                'uri' => 'id-2',
                'size' => strlen($object),
                'calendardata' => $object,
                'lastmodified' => null,
            ],
        ];

        $result = $backend->getMultipleCalendarObjects($returnedId, ['id-1', 'id-2']);

        foreach ($check as $index => $props) {
            foreach ($props as $key => $value) {
                if ('lastmodified' !== $key) {
                    $this->assertEquals($value, $result[$index][$key]);
                } else {
                    $this->assertTrue(isset($result[$index][$key]));
                }
            }
        }
    }

    public function testGetCalendarObjects()
    {
        $backend = $this->backend;
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $data = $backend->getCalendarObjects($returnedId, 'random-id');

        $this->assertEquals(1, count($data));
        $data = $data[0];

        $this->assertEquals('random-id', $data['uri']);
        $this->assertEquals('"'.md5($object).'"', $data['etag']);
        $this->assertEquals(strlen($object), $data['size']);
        $this->assertEquals('vevent', $data['component']);
    }

    public function testGetCalendarObjectByUID()
    {
        $backend = $this->backend;
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

    public function testUpdateCalendarObject()
    {
        $backend = $this->backend;
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $object2 = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20130101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->updateCalendarObject($returnedId, 'random-id', $object2);

        $data = $backend->getCalendarObject($returnedId, 'random-id');

        $this->assertEquals($object2, $data['calendardata']);
        $this->assertEquals('random-id', $data['uri']);
    }

    public function testDeleteCalendarObject()
    {
        $backend = $this->backend;
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->deleteCalendarObject($returnedId, 'random-id');

        $data = $backend->getCalendarObject($returnedId, 'random-id');
        $this->assertNull($data);
    }

    public function testCalendarQueryNoResult()
    {
        $backend = $this->backend;
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
        ], $backend->calendarQuery([1, 1], $filters));
    }

    public function testCalendarQueryTodo()
    {
        $id = $this->generateId();

        $backend = $this->backend;
        $backend->createCalendarObject($id, 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject($id, 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

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
        ], $backend->calendarQuery($id, $filters));
    }

    public function testCalendarQueryTodoNotMatch()
    {
        $id = $this->generateId();

        $backend = $this->backend;
        $backend->createCalendarObject($id, 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject($id, 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

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
        ], $backend->calendarQuery($id, $filters));
    }

    public function testCalendarQueryNoFilter()
    {
        $id = $this->generateId();

        $backend = $this->backend;
        $backend->createCalendarObject($id, 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject($id, 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => null,
        ];

        $result = $backend->calendarQuery($id, $filters);
        $this->assertTrue(in_array('todo', $result));
        $this->assertTrue(in_array('event', $result));
    }

    public function testCalendarQueryTimeRange()
    {
        $id = $this->generateId();

        $backend = $this->backend;
        $backend->createCalendarObject($id, 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject($id, 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject($id, 'event2', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

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
        ], $backend->calendarQuery($id, $filters));
    }

    public function testCalendarQueryTimeRangeNoEnd()
    {
        $id = $this->generateId();

        $backend = $this->backend;
        $backend->createCalendarObject($id, 'todo', "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject($id, 'event', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject($id, 'event2', "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

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
        ], $backend->calendarQuery($id, $filters));
    }

    public function testGetChanges()
    {
        $backend = $this->backend;
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

        $result = $backend->getChangesForCalendar($id, $currentToken, '1');

        $this->assertEquals([
            'syncToken' => 6,
            'modified' => ['todo1.ics'],
            'deleted' => ['todo2.ics'],
            'added' => ['todo3.ics'],
        ], $result);
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

        $backend = $this->backend;
        $id = $backend->createSubscription('principals/user1', 'sub1', $props);

        $subs = $backend->getSubscriptionsForUser('principals/user1');

        $expected = $props;
        $expected['id'] = $id;
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
        $props = [];
        $backend = $this->backend;
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

        $backend = $this->backend;
        $id = $backend->createSubscription('principals/user1', 'sub1', $props);

        $newProps = [
            '{DAV:}displayname' => 'new displayname',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/cal2.ics', false),
        ];

        $propPatch = new DAV\PropPatch($newProps);
        $backend->updateSubscription($id, $propPatch);
        $result = $propPatch->commit();

        $this->assertTrue($result);

        $subs = $backend->getSubscriptionsForUser('principals/user1');

        $expected = array_merge($props, $newProps);
        $expected['id'] = $id;
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

        $backend = $this->backend;
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

        $backend = $this->backend;
        $id = $backend->createSubscription('principals/user1', 'sub1', $props);

        $newProps = [
            '{DAV:}displayname' => 'new displayname',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/cal2.ics', false),
        ];

        $backend->deleteSubscription($id);

        $subs = $backend->getSubscriptionsForUser('principals/user1');
        $this->assertEquals(0, count($subs));
    }

    public function testSchedulingMethods()
    {
        $backend = $this->backend;

        $calData = "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n";

        $backend->createSchedulingObject(
            'principals/user1',
            'schedule1.ics',
            $calData
        );

        $expected = [
            'calendardata' => $calData,
            'uri' => 'schedule1.ics',
            'etag' => '"'.md5($calData).'"',
            'size' => strlen($calData),
        ];

        $result = $backend->getSchedulingObject('principals/user1', 'schedule1.ics');
        foreach ($expected as $k => $v) {
            $this->assertArrayHasKey($k, $result);
            $this->assertEquals($v, $result[$k]);
        }

        $results = $backend->getSchedulingObjects('principals/user1');

        $this->assertEquals(1, count($results));
        $result = $results[0];
        foreach ($expected as $k => $v) {
            $this->assertEquals($v, $result[$k]);
        }

        $backend->deleteSchedulingObject('principals/user1', 'schedule1.ics');
        $result = $backend->getSchedulingObject('principals/user1', 'schedule1.ics');

        $this->assertNull($result);
    }

    public function testGetInvites()
    {
        $backend = $this->backend;
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

    public function testUpdateInvites()
    {
        $backend = $this->backend;

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
            'id' => 'foo',
            'principaluri' => 'principals/user2',
            '{http://calendarserver.org/ns/}getctag' => 'http://sabre.io/ns/sync/1',
            '{http://sabredav.org/ns}sync-token' => '1',
            'share-access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
            'read-only' => true,
            'share-resource-uri' => 'bar',
        ];
        $calendars = $backend->getCalendarsForUser('principals/user2');
        foreach ($expectedCalendar as $k => $v) {
            if ('id' == $k) {
                $this->assertEquals($calendars[0][$k][0], $calendar['id'][0]);
            } elseif ('share-resource-uri' == $k) {
                $this->assertEquals(strpos($calendars[0][$k], '/ns/share/'), 0);
            } else {
                $this->assertEquals(
                    $v,
                    $calendars[0][$k],
                    'Key '.$k.' in calendars array did not have the expected value.'
                );
            }
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
}
