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
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $u = $this->getInstance();
        $u->createFile('test');
    }

    public function testCreateDirectory()
    {
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $u = $this->getInstance();
        $u->createDirectory('test');
    }

    public function testGetChildProxyRead()
    {
        $u = $this->getInstance();
        $child = $u->getChild('calendar-proxy-read');
        $this->assertInstanceOf('Sabre\\CalDAV\\Principal\\ProxyRead', $child);
    }

    public function testGetChildProxyWrite()
    {
        $u = $this->getInstance();
        $child = $u->getChild('calendar-proxy-write');
        $this->assertInstanceOf('Sabre\\CalDAV\\Principal\\ProxyWrite', $child);
    }

    public function testGetChildNotFound()
    {
        $this->expectException('Sabre\DAV\Exception\NotFound');
        $u = $this->getInstance();
        $child = $u->getChild('foo');
    }

    public function testGetChildNotFound2()
    {
        $this->expectException('Sabre\DAV\Exception\NotFound');
        $u = $this->getInstance();
        $child = $u->getChild('random');
    }

    public function testGetChildren()
    {
        $u = $this->getInstance();
        $children = $u->getChildren();
        $this->assertEquals(2, count($children));
        $this->assertInstanceOf('Sabre\\CalDAV\\Principal\\ProxyRead', $children[0]);
        $this->assertInstanceOf('Sabre\\CalDAV\\Principal\\ProxyWrite', $children[1]);
    }

    public function testChildExist()
    {
        $u = $this->getInstance();
        $this->assertTrue($u->childExists('calendar-proxy-read'));
        $this->assertTrue($u->childExists('calendar-proxy-write'));
        $this->assertFalse($u->childExists('foo'));
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
        $this->assertEquals($expected, $u->getACL());
    }
}
