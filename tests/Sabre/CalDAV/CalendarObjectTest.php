<?php

require_once 'Sabre/CalDAV/TestUtil.php';

class Sabre_CalDAV_CalendarObjectTest extends PHPUnit_Framework_TestCase {

    protected $backend;
    protected $calendar;
    protected $authBackend;

    function setup() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $this->backend = Sabre_CalDAV_TestUtil::getBackend();
        $this->authBackend = new Sabre_DAV_Auth_MockBackend('realm');
        $this->authBackend->setCurrentUser('principals/user1');

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1,count($calendars));
        $this->calendar = new Sabre_CalDAV_Calendar($this->authBackend,$this->backend, $calendars[0]);

    }

    function teardown() {

        unset($this->calendar);
        unset($this->backend);

    }

    function testSetup() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $this->assertType('string',$children[0]->getName());
        $this->assertType('string',$children[0]->get());
        $this->assertType('string',$children[0]->getETag());
        $this->assertEquals('text/calendar', $children[0]->getContentType());

    }

    /**
     * @depends testSetup
     */
    function testPut() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        $newData = 'testString';

        $children[0]->put($newData);
        $this->assertEquals($newData, $children[0]->get());

    }

    /**
     * @depends testSetup
     */
    function testGetProperties() {
        
        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $obj = $children[0];
        
        $result = $obj->getProperties(array('{urn:ietf:params:xml:ns:caldav}calendar-data'));

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-data', $result);
        $this->assertType('string', $result['{urn:ietf:params:xml:ns:caldav}calendar-data']);

    }

}
