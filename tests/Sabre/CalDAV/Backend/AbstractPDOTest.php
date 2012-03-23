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
        ));
        $calendars = $backend->getCalendarsForUser('principals/user2');

        $elementCheck = array(
            'id'                => $returnedId,
            'uri'               => 'somerandomid',
            '{DAV:}displayname' => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
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

        $backend->createCalendarObject($returnedId, 'random-id', 'calendar-data');

        $data = $backend->getCalendarObject($returnedId,'random-id');

        $this->assertEquals('calendar-data', $data['calendardata']);
        $this->assertEquals($returnedId, $data['calendarid']);
        $this->assertEquals('random-id', $data['uri']);


    }

    /**
     * @depends testCreateCalendarObject
     */
    function testGetCalendarObjects() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $backend->createCalendarObject($returnedId, 'random-id', 'calendar-data');

        $data = $backend->getCalendarObjects($returnedId,'random-id');

        $this->assertEquals(1, count($data));
        $data = $data[0];

        $this->assertEquals($returnedId, $data['calendarid']);
        $this->assertEquals('random-id', $data['uri']);
        $this->assertEquals(13,$data['size']);


    }

    /**
     * @depends testCreateCalendarObject
     */
    function testUpdateCalendarObject() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $backend->createCalendarObject($returnedId, 'random-id', 'calendar-data');
        $backend->updateCalendarObject($returnedId, 'random-id', 'calendar-data2');

        $data = $backend->getCalendarObject($returnedId,'random-id');

        $this->assertEquals('calendar-data2', $data['calendardata']);
        $this->assertEquals($returnedId, $data['calendarid']);
        $this->assertEquals('random-id', $data['uri']);


    }

    /**
     * @depends testCreateCalendarObject
     */
    function testDeleteCalendarObject() {

        $backend = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $backend->createCalendarObject($returnedId, 'random-id', 'calendar-data');
        $backend->deleteCalendarObject($returnedId, 'random-id');

        $data = $backend->getCalendarObject($returnedId,'random-id');
        $this->assertNull($data);

    }

    function testCalendarQuery() {

        $abstract = new Sabre_CalDAV_Backend_PDO($this->pdo);
        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
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
            'event1.ics',
        ), $abstract->calendarQuery(1, $filters));

    }
}
