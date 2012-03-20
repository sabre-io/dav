<?php

require_once 'Sabre/CardDAV/Backend/Mock.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';

abstract class Sabre_CardDAV_AbstractPluginTest extends PHPUnit_Framework_TestCase {

    /**
     * @var Sabre_CardDAV_Plugin
     */
    protected $plugin;
    /**
     * @var Sabre_DAV_Server
     */
    protected $server;
    /**
     * @var Sabre_CardDAV_MockBackend
     */
    protected $backend;

    function setUp() {

        $this->backend = new Sabre_CardDAV_Backend_Mock();
        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();

        $tree = array(
            new Sabre_CardDAV_AddressBookRoot($principalBackend, $this->backend),
            new Sabre_DAVACL_PrincipalCollection($principalBackend)
        );

        $this->plugin = new Sabre_CardDAV_Plugin();
        $this->plugin->directories = array('directory');
        $this->server = new Sabre_DAV_Server($tree);
        $this->server->addPlugin($this->plugin);
        $this->server->debugExceptions = true;

    }

}
