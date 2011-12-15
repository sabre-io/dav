<?php

require_once 'Sabre/DAVACL/MockPrincipalBackend.php';

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

        $this->assertEquals(array('calendar-auto-schedule'), $plugin->getFeatures());

    }

    public function testPrincipalProperties() {

        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();

        $tree = array(
            new Sabre_DAVACL_PrincipalCollection($principalBackend),
        );

        $server = new Sabre_DAV_Server($tree);

        $acl = new Sabre_DAVACL_Plugin();
        $server->addPlugin($acl);

        $plugin = new Sabre_CalDAV_Schedule_Plugin();
        $server->addPlugin($plugin);

        $result = $server->getPropertiesForChildren('principals',array(
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-inbox-URL',
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-outbox-URL',
        ));

        $expected = array(
            'principals/admin/' => array(
                '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-inbox-URL' => new Sabre_DAV_Property_Href('schedule/admin/inbox'),
                '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-outbox-URL' => new Sabre_DAV_Property_Href('schedule/admin/outbox'),
            ),
            'principals/user1/' => array(
                '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-inbox-URL' => new Sabre_DAV_Property_Href('schedule/user1/inbox'),
                '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-outbox-URL' => new Sabre_DAV_Property_Href('schedule/user1/outbox'),
            ),
            'principals/user2/' => array(
                '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-inbox-URL' => new Sabre_DAV_Property_Href('schedule/user2/inbox'),
                '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-outbox-URL' => new Sabre_DAV_Property_Href('schedule/user2/outbox'),
            ),
        );
        $this->assertEquals($expected,$result);

    }


}
