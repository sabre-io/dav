<?php

class Sabre_CalDAV_Schedule_PluginTest extends PHPUnit_Framework_TestCase {

    /**
     * @expectedException Sabre_DAV_Exception
     */
    public function testInitializeNoACL() {

        $plugin = new Sabre_CalDAV_Schedule_Plugin();
        $server = new Sabre_DAV_Server();
        $server->addPlugin($plugin);

    }

    public function testInitialize() {

        $plugin = new Sabre_CalDAV_Schedule_Plugin();

        $server = new Sabre_DAV_Server();
        $acl = new Sabre_DAVACL_Plugin();
        $server->addPlugin($acl);
        $server->addPlugin($plugin);

        $ns = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}';
        $this->assertEquals($ns . 'schedule-outbox', $server->resourceTypeMapping['Sabre_CalDAV_Schedule_IOutbox']);
        $this->assertEquals($ns . 'schedule-inbox', $server->resourceTypeMapping['Sabre_CalDAV_Schedule_IInbox']);
        $this->assertEquals('Calendar user addresses', $acl->principalSearchPropertySet[$ns . 'calendar-user-address-set']);

        $this->assertEquals(array(), $plugin->getFeatures());

    }


}

?>
