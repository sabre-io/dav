<?php

require_once 'Sabre/CalDAV/TestUtil.php';
require_once 'Sabre/DAV/Auth/MockBackend.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';

class Sabre_CalDAV_CalendarObjectTest extends PHPUnit_Framework_TestCase {

    protected $backend;
    protected $calendar;
    protected $principalBackend;

    function setup() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $this->backend = Sabre_CalDAV_TestUtil::getBackend();
        $this->principalBackend = new Sabre_DAVACL_MockPrincipalBackend;

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1,count($calendars));
        $this->calendar = new Sabre_CalDAV_Calendar($this->principalBackend,$this->backend, $calendars[0]);

    }

    function teardown() {

        unset($this->calendar);
        unset($this->backend);

    }

    function testSetup() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $this->assertInternalType('string',$children[0]->getName());
        $this->assertInternalType('string',$children[0]->get());
        $this->assertInternalType('string',$children[0]->getETag());
        $this->assertEquals('text/calendar', $children[0]->getContentType());

    }

    /**
     * @depends testSetup
     */
    function testPut() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        $newData = Sabre_CalDAV_TestUtil::getTestCalendarData();

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
        $this->assertInternalType('string', $result['{urn:ietf:params:xml:ns:caldav}calendar-data']);

    }

    /**
     * @depends testSetup
     */
    function testDelete() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $obj = $children[0];
        $obj->delete();

        $children2 =  $this->calendar->getChildren();
        $this->assertEquals(count($children)-1, count($children2));

    }

    /**
     * @depends testSetup
     */
    function testUpdateProperties() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $obj = $children[0];

        $this->assertFalse($obj->updateProperties(array('bla' => 'bla')));

    }

    /**
     * @depends testSetup
     */
    function testGetLastModified() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $obj = $children[0];

        $lastMod = $obj->getLastModified();
        $this->assertTrue(is_int($lastMod) || ctype_digit($lastMod));

    }

    /**
     * @depends testSetup
     */
    function testGetSize() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $obj = $children[0];

        $size = $obj->getSize();
        $this->assertInternalType('int', $size);

    }

    function testGetOwner() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $obj = $children[0];
        $this->assertEquals('principals/user1', $obj->getOwner());

    }

    function testGetGroup() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $obj = $children[0];
        $this->assertNull($obj->getGroup());

    }

    function testGetACL() {

        $expected = array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => 'true',
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1',
                'protected' => 'true',
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => 'true',
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => 'true',
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => 'true',
            ),
        );

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $obj = $children[0];
        $this->assertEquals($expected, $obj->getACL());

    }

    /**
     * @expectedException Sabre_DAV_Exception_MethodNotAllowed
     */
    function testSetACL() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);
        
        $obj = $children[0];
        $obj->setACL(array());

    }

}
