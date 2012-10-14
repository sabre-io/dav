<?php

class Sabre_CalDAV_Schedule_OutboxTest extends PHPUnit_Framework_TestCase {

    function testSetup() {

        $outbox = new Sabre_CalDAV_Schedule_Outbox('principals/user1');
        $this->assertEquals('outbox', $outbox->getName());
        $this->assertEquals(array(), $outbox->getChildren());
        $this->assertEquals('principals/user1', $outbox->getOwner());
        $this->assertEquals(null, $outbox->getGroup());

        $this->assertEquals(array(
            array(
                'privilege' => '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-query-freebusy',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-post-vevent',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
        ), $outbox->getACL());

        $ok = false;
        try {
            $outbox->setACL(array());
        } catch (Sabre_DAV_Exception_MethodNotAllowed $e) {
            $ok = true;
        }
        if (!$ok) {
            $this->fail('Exception was not emitted');
        }

    }

    function testGetSupportedPrivilegeSet() {

        $outbox = new Sabre_CalDAV_Schedule_Outbox('principals/user1');
        $r = $outbox->getSupportedPrivilegeSet();

        $ok = 0;
        foreach($r['aggregates'] as $priv) {

            if ($priv['privilege'] == '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-query-freebusy') {
                $ok++;
            }
            if ($priv['privilege'] == '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-post-vevent') {
                $ok++;
            }
        }

        $this->assertEquals(2, $ok, "We're missing one or more privileges");

    }


}
