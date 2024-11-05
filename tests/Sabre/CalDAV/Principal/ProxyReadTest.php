<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Principal;

use Sabre\DAVACL;

class ProxyReadTest extends \PHPUnit\Framework\TestCase
{
    protected $backend;

    public function getInstance()
    {
        $backend = new DAVACL\PrincipalBackend\Mock();
        $principal = new ProxyRead($backend, [
            'uri' => 'principal/user',
        ]);
        $this->backend = $backend;

        return $principal;
    }

    public function testGetName()
    {
        $i = $this->getInstance();
        self::assertEquals('calendar-proxy-read', $i->getName());
    }

    public function testGetDisplayName()
    {
        $i = $this->getInstance();
        self::assertEquals('calendar-proxy-read', $i->getDisplayName());
    }

    public function testGetLastModified()
    {
        $i = $this->getInstance();
        self::assertNull($i->getLastModified());
    }

    public function testDelete()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $i = $this->getInstance();
        $i->delete();
    }

    public function testSetName()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $i = $this->getInstance();
        $i->setName('foo');
    }

    public function testGetAlternateUriSet()
    {
        $i = $this->getInstance();
        self::assertEquals([], $i->getAlternateUriSet());
    }

    public function testGetPrincipalUri()
    {
        $i = $this->getInstance();
        self::assertEquals('principal/user/calendar-proxy-read', $i->getPrincipalUrl());
    }

    public function testGetGroupMemberSet()
    {
        $i = $this->getInstance();
        self::assertEquals([], $i->getGroupMemberSet());
    }

    public function testGetGroupMembership()
    {
        $i = $this->getInstance();
        self::assertEquals([], $i->getGroupMembership());
    }

    public function testSetGroupMemberSet()
    {
        $i = $this->getInstance();
        $i->setGroupMemberSet(['principals/foo']);

        $expected = [
            $i->getPrincipalUrl() => ['principals/foo'],
        ];

        self::assertEquals($expected, $this->backend->groupMembers);
    }
}
