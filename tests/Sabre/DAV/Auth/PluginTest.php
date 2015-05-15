<?php

namespace Sabre\DAV\Auth;

use Sabre\HTTP;
use Sabre\DAV;

require_once 'Sabre/HTTP/ResponseMock.php';

class PluginTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $this->assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('auth'));
        $this->assertInternalType('array', $plugin->getPluginInfo());

    }

    /**
     * @depends testInit
     */
    function testAuthenticate() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
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

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $backend = new Backend\Mock();
        $backend->fail = true;

        $plugin = new Plugin($backend);
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);

    }

    /**
     * @depends testAuthenticate
     */
    function testMultipleBackend() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $backend1 = new Backend\Mock();
        $backend2 = new Backend\Mock();
        $backend2->fail = true;

        $plugin = new Plugin();
        $plugin->addBackend($backend1);
        $plugin->addBackend($backend2);

        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);

        $this->assertEquals('principals/admin', $plugin->getCurrentPrincipal());

    }

    /**
     * @depends testInit
     * @expectedException Sabre\DAV\Exception
     */
    function testNoAuthBackend() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));

        $plugin = new Plugin();
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);

    }

    /**
     * @depends testInit
     * @expectedException Sabre\DAV\Exception
     */
    function testInvalidCheckResponse() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $backend = new Backend\Mock();
        $backend->invalidCheckResponse = true;

        $plugin = new Plugin($backend);
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);

    }

    /**
     * @depends testAuthenticate
     */
    function testGetCurrentPrincipal() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);
        $this->assertEquals('principals/admin', $plugin->getCurrentPrincipal());

    }

    /**
     * @depends testAuthenticate
     */
    function testGetCurrentUser() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);
        $this->assertEquals('admin', $plugin->getCurrentUser());

    }

    /**
     * @depends testInit
     */
    function testWhiteList() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock());
        $plugin->setWhiteList(['signup', 'signin']);
        $fakeServer->addPlugin($plugin);

        $this->assertTrue(
            $fakeServer->emit(
                'beforeMethod',
                [
                    new HTTP\Request('GET', '/signin'),
                    new HTTP\Response()
                ]
            )
        );
        $this->assertEquals(null, $plugin->getCurrentUser());

    }

    /**
     * @depends testInit
     */
    function testWhiteListMultipleBackends() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin1 = new Plugin(new Backend\Mock());
        $plugin1->setWhiteList(['signup', 'signin']);
        $fakeServer->addPlugin($plugin1);
        $fakeServer->addPlugin($plugin2 = new Plugin(new Backend\Mock()));

        $this->assertTrue(
            $fakeServer->emit(
                'beforeMethod',
                [
                    new HTTP\Request('GET', '/signin'),
                    new HTTP\Response()
                ]
            )
        );
        $this->assertEquals(null, $plugin1->getCurrentUser());
        $this->assertEquals('admin', $plugin2->getCurrentUser());

    }

}

