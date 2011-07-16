<?php

require_once 'Sabre/DAVACL/MockPrincipalBackend.php';

class Sabre_CardDAV_PluginTest extends PHPUnit_Framework_TestCase {

    private $plugin;
    private $server;
    private $backend;

    function setUp() {

        $this->backend = new Sabre_CardDAV_MockBackend();
        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();

        $tree = array(
            new Sabre_CardDAV_AddressBookRoot($principalBackend, $this->backend),
            new Sabre_DAVACL_PrincipalCollection($principalBackend)
        );

        $this->plugin = new Sabre_CardDAV_Plugin();
        $this->server = new Sabre_DAV_Server($tree);
        $this->server->addPlugin($this->plugin);

    }

    function testConstruct() {

        $this->assertEquals('card', $this->server->xmlNamespaces[Sabre_CardDAV_Plugin::NS_CARDDAV]);
        $this->assertEquals('{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook', $this->server->resourceTypeMapping['Sabre_CardDAV_IAddressBook']);
        
        $this->assertTrue(in_array('addressbook', $this->plugin->getFeatures()));

    }

    function testSupportedReportSet() {

        $this->assertEquals(array(
            '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-multiget',
        ), $this->plugin->getSupportedReportSet('addressbooks/user1/book1'));

    }

    function testSupportedReportSetEmpty() {

        $this->assertEquals(array(
        ), $this->plugin->getSupportedReportSet(''));

    }

    function testAddressBookHomeSet() {

        $result = $this->server->getProperties('principals/user1', array('{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-home-set'));

        $this->assertEquals(1, count($result));
        $this->assertTrue(isset($result['{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-home-set']));
        $this->assertEquals('addressbooks/user1/', $result['{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-home-set']->getHref());

    }


}
