<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;

class PrincipalTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertTrue($principal instanceof Principal);
    }

    public function testConstructNoUri()
    {
        $this->expectException('Sabre\DAV\Exception');
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, []);
    }

    public function testGetName()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertEquals('admin', $principal->getName());
    }

    public function testGetDisplayName()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertEquals('admin', $principal->getDisplayname());

        $principal = new Principal($principalBackend, [
            'uri' => 'principals/admin',
            '{DAV:}displayname' => 'Mr. Admin',
        ]);
        self::assertEquals('Mr. Admin', $principal->getDisplayname());
    }

    public function testGetProperties()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, [
            'uri' => 'principals/admin',
            '{DAV:}displayname' => 'Mr. Admin',
            '{http://www.example.org/custom}custom' => 'Custom',
            '{http://sabredav.org/ns}email-address' => 'admin@example.org',
        ]);

        $keys = [
            '{DAV:}displayname',
            '{http://www.example.org/custom}custom',
            '{http://sabredav.org/ns}email-address',
        ];
        $props = $principal->getProperties($keys);

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $props);
        }

        self::assertEquals('Mr. Admin', $props['{DAV:}displayname']);

        self::assertEquals('admin@example.org', $props['{http://sabredav.org/ns}email-address']);
    }

    public function testUpdateProperties()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);

        $propPatch = new DAV\PropPatch(['{DAV:}yourmom' => 'test']);

        $result = $principal->propPatch($propPatch);
        $result = $propPatch->commit();
        self::assertTrue($result);
    }

    public function testGetPrincipalUrl()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertEquals('principals/admin', $principal->getPrincipalUrl());
    }

    public function testGetAlternateUriSet()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, [
            'uri' => 'principals/admin',
            '{DAV:}displayname' => 'Mr. Admin',
            '{http://www.example.org/custom}custom' => 'Custom',
            '{http://sabredav.org/ns}email-address' => 'admin@example.org',
            '{DAV:}alternate-URI-set' => [
                'mailto:admin+1@example.org',
                'mailto:admin+2@example.org',
                'mailto:admin@example.org',
            ],
        ]);

        $expected = [
            'mailto:admin+1@example.org',
            'mailto:admin+2@example.org',
            'mailto:admin@example.org',
        ];

        self::assertEquals($expected, $principal->getAlternateUriSet());
    }

    public function testGetAlternateUriSetEmpty()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, [
            'uri' => 'principals/admin',
        ]);

        $expected = [];

        self::assertEquals($expected, $principal->getAlternateUriSet());
    }

    public function testGetGroupMemberSet()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertEquals([], $principal->getGroupMemberSet());
    }

    public function testGetGroupMembership()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertEquals([], $principal->getGroupMembership());
    }

    public function testSetGroupMemberSet()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $principal->setGroupMemberSet(['principals/foo']);

        self::assertEquals([
            'principals/admin' => ['principals/foo'],
        ], $principalBackend->groupMembers);
    }

    public function testGetOwner()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertEquals('principals/admin', $principal->getOwner());
    }

    public function testGetGroup()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertNull($principal->getGroup());
    }

    public function testGetACl()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertEquals([
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ], $principal->getACL());
    }

    public function testSetACl()
    {
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $principal->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        self::assertNull($principal->getSupportedPrivilegeSet());
    }
}
