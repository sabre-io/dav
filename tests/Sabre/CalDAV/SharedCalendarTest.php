<?php

class Sabre_CalDAV_SharedCalendarTest extends PHPUnit_Framework_TestCase {

    protected $backend;

    function getInstance(array $props = null) {

        if (is_null($props)) {
            $props = array(
                'id' => 1,
                '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/original',
                '{http://sabredav.org/ns}owner-principal' => 'principals/owner',
                '{http://sabredav.org/ns}read-only' => false,
                'principaluri' => 'principals/sharee',
            );
        }

        $this->backend = new Sabre_CalDAV_Backend_Mock(
            array($props),
            array(),
            array()
        );
        $this->backend->updateShares(1, array(
            array(
                'href' => 'mailto:removeme@example.org',
                'commonName' => 'To be removed',
                'readOnly' => true,
            ),
        ), array()); 

        $pBackend = new Sabre_DAVACL_MockPrincipalBackend();
        return new Sabre_CalDAV_SharedCalendar($pBackend, $this->backend, $props); 

    }

    function testGetSharedUrl() {
        $this->assertEquals('calendars/owner/original', $this->getInstance()->getSharedUrl());
    }


    function testGetOwner() {
        $this->assertEquals('principals/owner', $this->getInstance()->getOwner());
    }

    function testGetACL() {

        $expected = array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/owner',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/owner/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner/calendar-proxy-read',
                'protected' => true,
            ),
            array(
                'privilege' => '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}read-free-busy',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/sharee',
                'protected' => true,
            ),
        );

        $this->assertEquals($expected, $this->getInstance()->getACL());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateInstanceMissingArg() {

        $this->getInstance(array(
            'id' => 1,
            '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/original',
            '{http://sabredav.org/ns}read-only' => false,
            'principaluri' => 'principals/sharee',
        ));

    }

}
