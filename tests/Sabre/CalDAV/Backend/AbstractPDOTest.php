<?php

abstract class Sabre_CalDAV_Backend_AbstractPDOTest extends PHPUnit_Framework_TestCase {

    protected $pdo;

    function testConstruct() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $this->assertTrue($backend instanceof Sabre_CalDAV_Backend_PDO);

    }

    /**
     * @depends testConstruct
     */
    function testGetCalendarsForUserNoCalendars() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals(array(),$calendars);

    }

    /**
     * @depends testConstruct
     */
    function testCreateCalendarAndFetch() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array(
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet(array('VEVENT')),
            '{DAV:}displayname' => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new Sabre_CalDAV_Property_ScheduleCalendarTransp('transparent'),
        ));
        $calendars = $backend->getCalendarsForUser('principals/user2');

        $elementCheck = array(
            'id'                => $returnedId,
            'uri'               => 'somerandomid',
            '{DAV:}displayname' => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new Sabre_CalDAV_Property_ScheduleCalendarTransp('transparent'),
        );

        $this->assertInternalType('array',$calendars);
        $this->assertEquals(1,count($calendars));

        foreach($elementCheck as $name=>$value) {

            $this->assertArrayHasKey($name, $calendars[0]);
            $this->assertEquals($value,$calendars[0][$name]);

        }

    }

    /**
     * @depends testConstruct
     */
    function testUpdateCalendarAndFetch() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2','somerandomid',array());

        // Updating the calendar
        $result = $backend->updateCalendar($newId,array(
            '{DAV:}displayname' => 'myCalendar',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new Sabre_CalDAV_Property_ScheduleCalendarTransp('transparent'),
        ));

        // Verifying the result of the update
        $this->assertEquals(true, $result);

        // Fetching all calendars from this user
        $calendars = $backend->getCalendarsForUser('principals/user2');

        // Checking if all the information is still correct
        $elementCheck = array(
            'id'                => $newId,
            'uri'               => 'somerandomid',
            '{DAV:}displayname' => 'myCalendar',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
            '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => '',
            '{http://calendarserver.org/ns/}getctag' => '2',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new Sabre_CalDAV_Property_ScheduleCalendarTransp('transparent'),
        );

        $this->assertInternalType('array',$calendars);
        $this->assertEquals(1,count($calendars));

        foreach($elementCheck as $name=>$value) {

            $this->assertArrayHasKey($name, $calendars[0]);
            $this->assertEquals($value,$calendars[0][$name]);

        }

    }

    /**
     * @depends testUpdateCalendarAndFetch
     */
    function testUpdateCalendarUnknownProperty() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2','somerandomid',array());

        // Updating the calendar
        $result = $backend->updateCalendar($newId,array(
            '{DAV:}displayname' => 'myCalendar',
            '{DAV:}yourmom'     => 'wittycomment',
        ));

        // Verifying the result of the update
        $this->assertEquals(array(
            '403' => array('{DAV:}yourmom' => null),
            '424' => array('{DAV:}displayname' => null),
        ), $result);

    }

    /**
     * @depends testCreateCalendarAndFetch
     */
    function testDeleteCalendar() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array(
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet(array('VEVENT')),
            '{DAV:}displayname' => 'Hello!',
        ));

        $backend->deleteCalendar($returnedId);

        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals(array(),$calendars);

    }

    /**
     * @depends testCreateCalendarAndFetch
     * @expectedException Sabre_DAV_Exception
     */
    function testCreateCalendarIncorrectComponentSet() {;

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2','somerandomid',array(
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => 'blabla',
        ));

    }

    function testCreateCalendarObject() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('20120101'),
            'lastoccurence' => strtotime('20120101')+(3600*24),
            'componenttype' => 'VEVENT',
        ), $result->fetch(PDO::FETCH_ASSOC));

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectNoComponent() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectDuration() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nDURATION:P2D\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('20120101'),
            'lastoccurence' => strtotime('20120101')+(3600*48),
            'componenttype' => 'VEVENT',
        ), $result->fetch(PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectNoDTEND() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime('2012-01-01 10:00:00'),
            'componenttype' => 'VEVENT',
        ), $result->fetch(PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectInfiniteReccurence() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nRRULE:FREQ=DAILY\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime(Sabre_CalDAV_Backend_PDO::MAX_DATE),
            'componenttype' => 'VEVENT',
        ), $result->fetch(PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectEndingReccurence() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nDTEND;VALUE=DATE-TIME:20120101T110000Z\r\nRRULE:FREQ=DAILY;COUNT=1000\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime('2012-01-01 11:00:00') + (3600 * 24 * 999),
            'componenttype' => 'VEVENT',
        ), $result->fetch(PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectTask() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nDUE;VALUE=DATE-TIME:20120101T100000Z\r\nEND:VTODO\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => null,
            'lastoccurence' => null,
            'componenttype' => 'VTODO',
        ), $result->fetch(PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testGetCalendarObjects() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $data = $backend->getCalendarObjects($returnedId,'random-id');

        $this->assertEquals(1, count($data));
        $data = $data[0];

        $this->assertEquals($returnedId, $data['calendarid']);
        $this->assertEquals('random-id', $data['uri']);
        $this->assertEquals(strlen($object),$data['size']);


    }

    /**
     * @depends testCreateCalendarObject
     */
    function testUpdateCalendarObject() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $object2 = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20130101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->updateCalendarObject($returnedId, 'random-id', $object2);

        $data = $backend->getCalendarObject($returnedId,'random-id');

        $this->assertEquals($object2, $data['calendardata']);
        $this->assertEquals($returnedId, $data['calendarid']);
        $this->assertEquals('random-id', $data['uri']);


    }

    /**
     * @depends testCreateCalendarObject
     */
    function testDeleteCalendarObject() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->deleteCalendarObject($returnedId, 'random-id');

        $data = $backend->getCalendarObject($returnedId,'random-id');
        $this->assertNull($data);

    }

    function testCalendarQueryNoResult() {

        $abstract = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VJOURNAL',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => null,
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
        ), $abstract->calendarQuery(1, $filters));

    }

    function testCalendarQueryTodo() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VTODO',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => null,
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
            "todo",
        ), $backend->calendarQuery(1, $filters));

    }
    function testCalendarQueryTodoNotMatch() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VTODO',
                    'comp-filters' => array(),
                    'prop-filters' => array(
                        array(
                            'name' => 'summary',
                            'text-match' => null,
                            'time-range' => null,
                            'param-filters' => array(),
                            'is-not-defined' => false,
                        ),
                    ),
                    'is-not-defined' => false,
                    'time-range' => null,
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
        ), $backend->calendarQuery(1, $filters));

    }

    function testCalendarQueryNoFilter() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $result = $backend->calendarQuery(1, $filters);
        $this->assertTrue(in_array('todo', $result));
        $this->assertTrue(in_array('event', $result));

    }

    function testCalendarQueryTimeRange() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event2", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => array(
                        'start' => new DateTime('20120103'),
                        'end'   => new DateTime('20120104'),
                    ),
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
            "event2",
        ), $backend->calendarQuery(1, $filters));

    }
    function testCalendarQueryTimeRangeNoEnd() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event2", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => array(
                        'start' => new DateTime('20120102'),
                        'end' => null,
                    ),
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
            "event2",
        ), $backend->calendarQuery(1, $filters));

    }
}
