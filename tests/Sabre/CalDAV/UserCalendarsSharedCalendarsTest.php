<?php

namespace Sabre\CalDAV;

use Sabre\DAVACL;

require_once 'Sabre/CalDAV/TestUtil.php';

/**
 * @covers Sabre\CalDAV\UserCalendars
 */
class UserCalendarsSharedCalendarsTest extends \PHPUnit_Framework_TestCase {

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

        $this->backend = new Backend\Mock(
            $calendars,
            array(),
            array()
        );

        return new UserCalendars($this->backend, array(
            'uri' => 'principals/user1'
        ));

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

            if ($child instanceof IShareableCalendar) {
                $hasShareable = true;
            }
            if ($child instanceof ISharedCalendar) {
                $hasShared = true;
            }
            if ($child instanceof Schedule\IOutbox) {
                $hasOutbox = true;
            }
            if ($child instanceof Notifications\ICollection) {
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
        $instance->shareReply('uri', SharingPlugin::STATUS_DECLINED, 'curi', '1');

    }

}
