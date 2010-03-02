<?php

require_once 'Sabre/CalDAV/TestUtil.php';

class Sabre_CalDAV_CalendarTest extends PHPUnit_Framework_TestCase {

    function setup() {

        $this->backend = Sabre_CalDAV_TestUtil::getBackend();

    }

    function teardown() {

        unset($this->backend);

    }

    function testSimple() {

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1,count($calendars));
        $calendar = new Sabre_CalDAV_Calendar($this->backend, $calendars[0]);
        $this->assertEquals($calendars[0]['uri'], $calendar->getName());

    }

    function testUpdateProperties() {

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1,count($calendars));
        $calendar = new Sabre_CalDAV_Calendar($this->backend, $calendars[0]);

        $result = $calendar->updateProperties(array(
            array(Sabre_DAV_Server::PROP_SET,'{DAV:}displayname','NewName'),
        ));

        $this->assertEquals(array(array('{DAV:}displayname',200)), $result);

        $calendars2 = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals('NewName',$calendars2[0]['{DAV:}displayname']);

    }

    function testGetProperties() {

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1,count($calendars));
        $calendar = new Sabre_CalDAV_Calendar($this->backend, $calendars[0]);

        $question = array(
            '{DAV:}resourcetype',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-data',
        );

        $result = $calendar->getProperties($question);

        foreach($question as $q) $this->assertArrayHasKey($q,$result);

        $this->assertTrue($result['{DAV:}resourcetype'] instanceof Sabre_DAV_Property_ResourceType);
        $rt = array('{urn:ietf:params:xml:ns:caldav}calendar','{DAV:}collection');
        foreach($rt as $rte) $this->assertTrue(in_array($rte, $result['{DAV:}resourcetype']->resourceType));

        $this->assertEquals(array('VEVENT','VTODO'), $result['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set']->getValue());
        

    }

    /**
     * @expectedException Sabre_DAV_Exception_FileNotFound
     */
    function testGetChildNotFound() {

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1,count($calendars));
        $calendar = new Sabre_CalDAV_Calendar($this->backend, $calendars[0]);
        $calendar->getChild('randomname');

    }

    function testGetChildren() {

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1,count($calendars));
        $calendar = new Sabre_CalDAV_Calendar($this->backend, $calendars[0]);
        
        $children = $calendar->getChildren();
        $this->assertEquals(1,count($children));

        $this->assertTrue($children[0] instanceof Sabre_CalDAV_CalendarObject);

    }

}
