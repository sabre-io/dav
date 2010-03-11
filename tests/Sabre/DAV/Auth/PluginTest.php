<?php

require_once 'Sabre/DAV/Auth/MockBackend.php';

class Sabre_DAV_Auth_PluginTest extends PHPUnit_Framework_TestCase {

    function testInit() {

        $fakeServer = new Sabre_DAV_Server(new Sabre_DAV_ObjectTree(new Sabre_DAV_SimpleDirectory('bla')));
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'realm');
        $this->assertTrue($plugin instanceof Sabre_DAV_Auth_Plugin);
        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('Sabre_DAV_Auth_Plugin'));

    }

    /**
     * @depends testInit
     */
    function testAuthenticate() {

        $fakeServer = new Sabre_DAV_Server(new Sabre_DAV_ObjectTree(new Sabre_DAV_SimpleDirectory('bla')));
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'realm');
        $fakeServer->addPlugin($plugin);
        $fakeServer->broadCastEvent('beforeMethod',array('GET'));

        $this->assertEquals(array(
            'userId' => 'admin',
        ), $plugin->getUserInfo());

    }

    function testCurrentUserPrincipal() {

        $fakeServer = new Sabre_DAV_Server(new Sabre_DAV_ObjectTree(new Sabre_DAV_SimpleDirectory('bla')));
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'realm');
        $fakeServer->addPlugin($plugin);


        $props = $fakeServer->getProperties('',array('{DAV:}current-user-principal'));
        $this->assertArrayHasKey('{DAV:}current-user-principal', $props);

        $this->assertEquals(Sabre_DAV_Property_Principal::UNAUTHENTICATED, $props['{DAV:}current-user-principal']->getType());

        // This will force the login
        $fakeServer->broadCastEvent('beforeMethod',array('GET'));

        $props = $fakeServer->getProperties('',array('{DAV:}current-user-principal'));
        $this->assertArrayHasKey('{DAV:}current-user-principal', $props);

        $this->assertEquals(Sabre_DAV_Property_Principal::HREF, $props['{DAV:}current-user-principal']->getType());
        $this->assertEquals('principals/admin', $props['{DAV:}current-user-principal']->getHref());
    }

    /**
     * @depends testInit
     * @expectedException Sabre_DAV_Exception_NotAuthenticated
     */
    function testAuthenticateFail() {

        $fakeServer = new Sabre_DAV_Server(new Sabre_DAV_ObjectTree(new Sabre_DAV_SimpleDirectory('bla')));
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'failme');
        $fakeServer->addPlugin($plugin);
        $fakeServer->broadCastEvent('beforeMethod',array('GET'));

    }

}

