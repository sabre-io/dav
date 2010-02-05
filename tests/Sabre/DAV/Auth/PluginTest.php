<?php

class Sabre_DAV_Auth_PluginTest extends PHPUnit_Framework_TestCase {

    function testInit() {

        $fakeServer = new Sabre_DAV_Server(new Sabre_DAV_ObjectTree(new Sabre_DAV_SimpleDirectory('bla')));
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_PluginMockBackend(),'realm');
        $this->assertTrue($plugin instanceof Sabre_DAV_Auth_Plugin);
        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('Sabre_DAV_Auth_Plugin'));

    }

    /**
     * @depends testInit
     */
    function testAuthenticate() {

        $fakeServer = new Sabre_DAV_Server(new Sabre_DAV_ObjectTree(new Sabre_DAV_SimpleDirectory('bla')));
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_PluginMockBackend(),'realm');
        $fakeServer->addPlugin($plugin);
        $fakeServer->broadCastEvent('beforeMethod',array('GET'));

        $this->assertEquals(array(), $plugin->getUserInfo());

    }

    /**
     * @depends testInit
     * @expectedException Sabre_DAV_Exception_NotAuthenticated
     */
    function testAuthenticateFail() {

        $fakeServer = new Sabre_DAV_Server(new Sabre_DAV_ObjectTree(new Sabre_DAV_SimpleDirectory('bla')));
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_PluginMockBackend(),'failme');
        $fakeServer->addPlugin($plugin);
        $fakeServer->broadCastEvent('beforeMethod',array('GET'));

    }

}

class Sabre_DAV_Auth_PluginMockBackend extends Sabre_DAV_Auth_Backend_Abstract {

    function authenticate(Sabre_DAV_Server $server, $realm) {

        if ($realm=='failme') return false;

        // An empty array == success too
        return array();

    }

}
