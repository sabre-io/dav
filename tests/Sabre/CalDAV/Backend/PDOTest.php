<?php

class Sabre_CalDAV_Backend_PDOTest extends PHPUnit_Framework_TestCase {
    
    private $pdo;

    function setup() {

        $this->pdo = null;
        if (file_exists(SABRE_TEMPDIR . '/testdb.sqlite'))
            unlink(SABRE_TEMPDIR . '/testdb.sqlite');

        $this->pdo = new PDO('sqlite:' . SABRE_TEMPDIR . '/testdb.sqlite');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $this->pdo->query('
CREATE TABLE calendarobjects ( 
	id integer primary key asc, 
    calendardata text, 
    uri text, 
    calendarid integer, 
    lastmodified integer
);
');

        $this->pdo->query('
CREATE TABLE calendars (
    id integer primary key asc, 
    principaluri text, 
    displayname text, 
    uri text,
    ctag integer,
    description text,
	calendarorder integer,
    calendarcolor text,
    timezone text
);');

    }

    function teardown() {

        $this->pdo = null;
        unlink(SABRE_TEMPDIR . '/testdb.sqlite');

    }

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
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());
        $calendars = $backend->getCalendarsForUser('principals/user2');

        $elementCheck = array(
            'id'                => $returnedId,
            'uri'               => 'somerandomid',
            '{DAV:}displayname' => '',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
        );

        $this->assertType('array',$calendars);
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
            array(Sabre_DAV_Server::PROP_SET,'{DAV:}displayname','myCalendar'),
        ));

        // Verifying the result of the update
        $this->assertEquals(array(
            array('{DAV:}displayname',200),
        ), $result);

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

        $this->assertType('array',$calendars);
        $this->assertEquals(1,count($calendars));
       
        foreach($elementCheck as $name=>$value) {

            $this->assertArrayHasKey($name, $calendars[0]);
            $this->assertEquals($value,$calendars[0][$name]);

        }


    }

}
