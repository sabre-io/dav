<?php

namespace Sabre\CalDAV;
use Sabre\DAVACL;

require_once 'Sabre/CalDAV/TestUtil.php';

class CalendarTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\CalDAV\Backend_PDO
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

    function setup() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');

        $this->backend = TestUtil::getBackend();

        $this->calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(2, count($this->calendars));
        $this->calendar = new Calendar($this->backend, $this->calendars[0]);


    }

    function teardown() {

        unset($this->backend);

    }

    function testSimple() {

        $this->assertEquals($this->calendars[0]['uri'], $this->calendar->getName());

    }

    /**
     * @depends testSimple
     */
    function testUpdateProperties() {

        $result = $this->calendar->updateProperties(array(
            '{DAV:}displayname' => 'NewName',
        ));

        $this->assertEquals(true, $result);

        $calendars2 = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals('NewName',$calendars2[0]['{DAV:}displayname']);

    }

    /**
     * @depends testSimple
     */
    function testGetProperties() {

        $question = array(
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-data',
            '{urn:ietf:params:xml:ns:caldav}supported-collation-set',
            '{DAV:}owner',
        );

        $result = $this->calendar->getProperties($question);

        foreach($question as $q) $this->assertArrayHasKey($q,$result);

        $this->assertEquals(array('VEVENT','VTODO'), $result['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set']->getValue());

        $this->assertTrue($result['{urn:ietf:params:xml:ns:caldav}supported-collation-set'] instanceof Property\SupportedCollationSet);

        $this->assertTrue($result['{DAV:}owner'] instanceof DAVACL\Property\Principal);
        $this->assertEquals('principals/user1', $result['{DAV:}owner']->getHref());

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotFound
     * @depends testSimple
     */
    function testGetChildNotFound() {

        $this->calendar->getChild('randomname');

    }

    /**
     * @depends testSimple
     */
    function testGetChildren() {

        $children = $this->calendar->getChildren();
        $this->assertEquals(1,count($children));

        $this->assertTrue($children[0] instanceof CalendarObject);

    }

    /**
     * @depends testGetChildren
     */
    function testChildExists() {

        $this->assertFalse($this->calendar->childExists('foo'));

        $children = $this->calendar->getChildren();
        $this->assertTrue($this->calendar->childExists($children[0]->getName()));
    }



    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testCreateDirectory() {

        $this->calendar->createDirectory('hello');

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testSetName() {

        $this->calendar->setName('hello');

    }

    function testGetLastModified() {

        $this->assertNull($this->calendar->getLastModified());

    }

    function testCreateFile() {

        $file = fopen('php://memory','r+');
        fwrite($file,TestUtil::getTestCalendarData());
        rewind($file);

        $this->calendar->createFile('hello',$file);

        $file = $this->calendar->getChild('hello');
        $this->assertTrue($file instanceof CalendarObject);

    }

    function testCreateFileNoSupportedComponents() {

        $file = fopen('php://memory','r+');
        fwrite($file,TestUtil::getTestCalendarData());
        rewind($file);

        $calendar = new Calendar($this->backend, $this->calendars[1]);
        $calendar->createFile('hello',$file);

        $file = $calendar->getChild('hello');
        $this->assertTrue($file instanceof CalendarObject);

    }

    function testDelete() {

        $this->calendar->delete();

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1, count($calendars));
    }

    function testGetOwner() {

        $this->assertEquals('principals/user1',$this->calendar->getOwner());

    }

    function testGetGroup() {

        $this->assertNull($this->calendar->getGroup());

    }

    function testGetACL() {

        $expected = array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ),
            array(
                'privilege' => '{' . Plugin::NS_CALDAV . '}read-free-busy',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ),
        );
        $this->assertEquals($expected, $this->calendar->getACL());

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testSetACL() {

        $this->calendar->setACL(array());

    }

    function testGetSupportedPrivilegesSet() {

        $result = $this->calendar->getSupportedPrivilegeSet();

        $this->assertEquals(
            '{' . Plugin::NS_CALDAV . '}read-free-busy',
            $result['aggregates'][0]['aggregates'][2]['privilege']
        );

    }


}
