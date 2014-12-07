<?php

namespace Sabre\DAV\Auth;

use Sabre\HTTP;
use Sabre\DAV;

require_once 'Sabre/HTTP/ResponseMock.php';

class PluginTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $this->assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('auth'));

    }

    /**
     * @depends testInit
     */
    function testAuthenticate() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $this->assertTrue(
            $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()])
        );

    }

    /**
     * @depends testInit
     * @expectedException Sabre\DAV\Exception\NotAuthenticated
     */
    function testAuthenticateFail() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $backend = new Backend\Mock();
        $backend->fail = true;

        $plugin = new Plugin($backend);
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);

    }

    /**
     * @depends testAuthenticate
     */
    function testGetCurrentPrincipal() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);
        $this->assertEquals('principals/admin', $plugin->getCurrentPrincipal());

    }

    /**
     * @depends testAuthenticate
     */
    function testGetCurrentUser() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);
        $this->assertEquals('admin', $plugin->getCurrentUser());

    }

}

