<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth;

use Sabre\DAV;
use Sabre\HTTP;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    public function testInit()
    {
        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock());
        self::assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        self::assertEquals($plugin, $fakeServer->getPlugin('auth'));
        self::assertIsArray($plugin->getPluginInfo());
    }

    /**
     * @depends testInit
     */
    public function testAuthenticate()
    {
        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock());
        $fakeServer->addPlugin($plugin);
        self::assertTrue(
            $fakeServer->emit('beforeMethod:GET', [new HTTP\Request('GET', '/'), new HTTP\Response()])
        );
    }

    /**
     * @depends testInit
     */
    public function testAuthenticateFail()
    {
        $this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);
        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $backend = new Backend\Mock();
        $backend->fail = true;

        $plugin = new Plugin($backend);
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new HTTP\Request('GET', '/'), new HTTP\Response()]);
    }

    /**
     * @depends testAuthenticateFail
     */
    public function testAuthenticateFailDontAutoRequire()
    {
        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $backend = new Backend\Mock();
        $backend->fail = true;

        $plugin = new Plugin($backend);
        $plugin->autoRequireLogin = false;
        $fakeServer->addPlugin($plugin);
        self::assertTrue(
            $fakeServer->emit('beforeMethod:GET', [new HTTP\Request('GET', '/'), new HTTP\Response()])
        );
        self::assertEquals(1, count($plugin->getLoginFailedReasons()));
    }

    /**
     * @depends testAuthenticate
     */
    public function testMultipleBackend()
    {
        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $backend1 = new Backend\Mock();
        $backend2 = new Backend\Mock();
        $backend2->fail = true;

        $plugin = new Plugin();
        $plugin->addBackend($backend1);
        $plugin->addBackend($backend2);

        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new HTTP\Request('GET', '/'), new HTTP\Response()]);

        self::assertEquals('principals/admin', $plugin->getCurrentPrincipal());
    }

    /**
     * @depends testInit
     */
    public function testNoAuthBackend()
    {
        $this->expectException(\Sabre\DAV\Exception::class);
        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));

        $plugin = new Plugin();
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new HTTP\Request('GET', '/'), new HTTP\Response()]);
    }

    /**
     * @depends testInit
     */
    public function testInvalidCheckResponse()
    {
        $this->expectException(\Sabre\DAV\Exception::class);
        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $backend = new Backend\Mock();
        $backend->invalidCheckResponse = true;

        $plugin = new Plugin($backend);
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new HTTP\Request('GET', '/'), new HTTP\Response()]);
    }

    /**
     * @depends testAuthenticate
     */
    public function testGetCurrentPrincipal()
    {
        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod:GET', [new HTTP\Request('GET', '/'), new HTTP\Response()]);
        self::assertEquals('principals/admin', $plugin->getCurrentPrincipal());
    }
}
