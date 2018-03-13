<?php declare (strict_types=1);

namespace Sabre\DAV\Auth;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\DAV\Psr7RequestWrapper;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP;

class PluginTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $fakeServer = new Server(new SimpleCollection('bla'), null, null, function(){});
        $plugin = new Plugin(new Backend\Mock());
        $this->assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('auth'));
        $this->assertInternalType('array', $plugin->getPluginInfo());

    }

    /**
     * @depends testInit
     */
    function testAuthenticate() {

        $fakeServer = new Server(new SimpleCollection('bla'), null, null, function(){});
        $plugin = new Plugin(new Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $this->assertTrue(
            $fakeServer->emit('beforeMethod:GET', [new Psr7RequestWrapper(new ServerRequest('GET', '/')), new HTTP\Response()])
        );

    }

    /**
     * @depends testInit
     * @expectedException \Sabre\DAV\Exception\NotAuthenticated
     */
    function testAuthenticateFail() {

        $fakeServer = new Server(new SimpleCollection('bla'), null, null, function(){});
        $backend = new Backend\Mock();
        $backend->fail = true;

        $plugin = new Plugin($backend);
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new Psr7RequestWrapper(new ServerRequest('GET', '/')), new HTTP\Response()]);

    }

    /**
     * @depends testAuthenticateFail
     */
    function testAuthenticateFailDontAutoRequire() {

        $fakeServer = new Server(new SimpleCollection('bla'), null, null, function(){});
        $backend = new Backend\Mock();
        $backend->fail = true;

        $plugin = new Plugin($backend);
        $plugin->autoRequireLogin = false;
        $fakeServer->addPlugin($plugin);
        $this->assertTrue(
            $fakeServer->emit('beforeMethod:GET', [new Psr7RequestWrapper(new ServerRequest('GET', '/')), new HTTP\Response()])
        );
        $this->assertEquals(1, count($plugin->getLoginFailedReasons()));

    }

    /**
     * @depends testAuthenticate
     */
    function testMultipleBackend() {

        $fakeServer = new Server(new SimpleCollection('bla'), null, null, function(){});
        $backend1 = new Backend\Mock();
        $backend2 = new Backend\Mock();
        $backend2->fail = true;

        $plugin = new Plugin();
        $plugin->addBackend($backend1);
        $plugin->addBackend($backend2);

        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new Psr7RequestWrapper(new ServerRequest('GET', '/')), new HTTP\Response()]);

        $this->assertEquals('principals/admin', $plugin->getCurrentPrincipal());

    }

    /**
     * @depends testInit
     * @expectedException \Sabre\DAV\Exception
     */
    function testNoAuthBackend() {

        $fakeServer = new Server(new SimpleCollection('bla'), null, null, function(){});

        $plugin = new Plugin();
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new Psr7RequestWrapper(new ServerRequest('GET', '/')), new HTTP\Response()]);

    }
    /**
     * @depends testInit
     * @expectedException \Sabre\DAV\Exception
     */
    function testInvalidCheckResponse() {

        $fakeServer = new Server(new SimpleCollection('bla'), null, null, function(){});
        $backend = new Backend\Mock();
        $backend->invalidCheckResponse = true;

        $plugin = new Plugin($backend);
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new Psr7RequestWrapper(new ServerRequest('GET', '/')), new HTTP\Response()]);

    }

    /**
     * @depends testAuthenticate
     */
    function testGetCurrentPrincipal() {

        $fakeServer = new Server(new SimpleCollection('bla'), null, null, function(){});
        $plugin = new Plugin(new Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new Psr7RequestWrapper(new ServerRequest('GET', '/')), new HTTP\Response()]);
        $this->assertEquals('principals/admin', $plugin->getCurrentPrincipal());

    }

}
