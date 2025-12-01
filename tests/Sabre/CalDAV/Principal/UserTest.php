<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Principal;

use Sabre\DAVACL;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function getInstance()
    {
        $backend = new DAVACL\PrincipalBackend\Mock();
        $backend->addPrincipal([
            'uri' => 'principals/user/calendar-proxy-read',
        ]);
        $backend->addPrincipal([
            'uri' => 'principals/user/calendar-proxy-write',
        ]);
        $backend->addPrincipal([
            'uri' => 'principals/user/random',
        ]);

        return new User($backend, [
            'uri' => 'principals/user',
        ]);
    }

    public function testCreateFile()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $u = $this->getInstance();
        $u->createFile('test');
    }

    public function testCreateDirectory()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $u = $this->getInstance();
        $u->createDirectory('test');
    }

    public function testGetChildProxyRead()
    {
        $u = $this->getInstance();
        $child = $u->getChild('calendar-proxy-read');
        self::assertInstanceOf(ProxyRead::class, $child);
    }

    public function testGetChildProxyWrite()
    {
        $u = $this->getInstance();
        $child = $u->getChild('calendar-proxy-write');
        self::assertInstanceOf(ProxyWrite::class, $child);
    }

    public function testGetChildNotFound()
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);
        $u = $this->getInstance();
        $child = $u->getChild('foo');
    }

    public function testGetChildNotFound2()
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);
        $u = $this->getInstance();
        $child = $u->getChild('random');
    }

    public function testGetChildren()
    {
        $u = $this->getInstance();
        $children = $u->getChildren();
        self::assertEquals(2, count($children));
        self::assertInstanceOf(ProxyRead::class, $children[0]);
        self::assertInstanceOf(ProxyWrite::class, $children[1]);
    }

    public function testChildExist()
    {
        $u = $this->getInstance();
        self::assertTrue($u->childExists('calendar-proxy-read'));
        self::assertTrue($u->childExists('calendar-proxy-write'));
        self::assertFalse($u->childExists('foo'));
    }

    public function testGetACL()
    {
        $expected = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user/calendar-proxy-write',
                'protected' => true,
            ],
        ];

        $u = $this->getInstance();
        self::assertEquals($expected, $u->getACL());
    }
}
