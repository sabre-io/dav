<?php

require_once 'Sabre/CalDAV/TestUtil.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';

/**
 * @covers Sabre_CalDAV_UserCalendars
 */
class Sabre_CalDAV_UserCalendarsSharedCalendarsTest extends PHPUnit_Framework_TestCase {

    protected $backend;

    function getInstance() {

        $calendars = array(
            array(
                'id' => 1,
                'principaluri' => 'principals/user1',
            ),
            array(
                'id' => 2,
                '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/cal1',
                '{http://sabredav.org/ns}owner-principal' => 'principal/owner',
                '{http://sabredav.org/ns}read-only' => false,
                'principaluri' => 'principals/user1',
            ),
        );

        $this->backend = new Sabre_CalDAV_Backend_Mock(
            $calendars,
            array(),
            array()
        );

        $pBackend = new Sabre_DAVACL_MockPrincipalBackend();
        return new Sabre_CalDAV_UserCalendars($pBackend, $this->backend, 'principals/user1');

    }

    function testSimple() {

        $instance = $this->getInstance();
        $this->assertEquals('user1', $instance->getName());

    }

    function testGetChildren() {

        $instance = $this->getInstance();
        $children = $instance->getChildren();
        $this->assertEquals(4, count($children));

        // Testing if we got all the objects back.
        $hasShareable = false;
        $hasShared = false;
        $hasOutbox = false;
        $hasNotifications = false;
        
        foreach($children as $child) {

            if ($child instanceof Sabre_CalDAV_IShareableCalendar) {
                $hasShareable = true;
            }
            if ($child instanceof Sabre_CalDAV_ISharedCalendar) {
                $hasShared = true;
            }
            if ($child instanceof Sabre_CalDAV_Schedule_IOutbox) {
                $hasOutbox = true;
            }
            if ($child instanceof Sabre_CalDAV_Notifications_ICollection) {
                $hasNotifications = true;
            }

        }
        if (!$hasShareable) $this->fail('Missing node!');
        if (!$hasShared) $this->fail('Missing node!');
        if (!$hasOutbox) $this->fail('Missing node!');
        if (!$hasNotifications) $this->fail('Missing node!'); 

    }
    
    function testShareReply() {

        $instance = $this->getInstance();
        $instance->shareReply('uri', Sabre_CalDAV_SharingPlugin::STATUS_DECLINED, 'curi', '1');

    }

}
