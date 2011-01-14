<?php

class Sabre_CalDAV_Principal_ProxyReadTest extends PHPUnit_Framework_TestCase {

    function getInstance() {

        $backend = new Sabre_DAVACL_MockPrincipalBackend();
        $principal = new Sabre_CalDAV_Principal_ProxyRead($backend, array(
            'uri' => 'principal/user',
        ));
        return $principal;

    }

    function testGetName() {

        $i = $this->getInstance();
        $this->assertEquals('calendar-proxy-read', $i->getName());

    }
    function testGetDisplayName() {

        $i = $this->getInstance();
        $this->assertEquals('calendar-proxy-read', $i->getDisplayName());

    }

    function testGetLastModified() {

        $i = $this->getInstance();
        $this->assertNull($i->getLastModified());

    }

    /**
     * @expectedException Sabre_DAV_Exception_Forbidden
     */
    function testDelete() {

        $i = $this->getInstance();
        $i->delete();

    }

    /**
     * @expectedException Sabre_DAV_Exception_Forbidden
     */
    function testSetName() {

        $i = $this->getInstance();
        $i->setName('foo');

    }

    function testGetAlternateUriSet() {

        $i = $this->getInstance();
        $this->assertEquals(array(), $i->getAlternateUriSet());

    }
    
    function testGetPrincipalUri() {

        $i = $this->getInstance();
        $this->assertEquals('principal/user/calendar-proxy-read', $i->getPrincipalUrl());

    }

    function testGetGroupMemberSet() {

        $i = $this->getInstance();
        $this->assertEquals(array(), $i->getGroupMemberSet());

    }

    function testGetGroupMembership() {

        $i = $this->getInstance();
        $this->assertEquals(array(), $i->getGroupMembership());

    }

    /**
     * @expectedException Sabre_DAV_Exception_Forbidden
     */
    function testSetGroupMemberSet() {

        $i = $this->getInstance();
        $i->setGroupMemberSet(array());

    }
}
