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
        $this->assertTrue($principal instanceof Principal);
    }

    /**
     * @expectedException \Sabre\DAV\Exception
     */
    public function testConstructNoUri()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, []);
    }

    public function testGetName()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals('admin', $principal->getName());
    }

    public function testGetDisplayName()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals('admin', $principal->getDisplayname());

        $principal = new Principal($principalBackend, [
            'uri' => 'principals/admin',
            '{DAV:}displayname' => 'Mr. Admin',
        ]);
        $this->assertEquals('Mr. Admin', $principal->getDisplayname());
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
            $this->assertArrayHasKey($key, $props);
        }

        $this->assertEquals('Mr. Admin', $props['{DAV:}displayname']);

        $this->assertEquals('admin@example.org', $props['{http://sabredav.org/ns}email-address']);
    }

    public function testUpdateProperties()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);

        $propPatch = new DAV\PropPatch(['{DAV:}yourmom' => 'test']);

        $result = $principal->propPatch($propPatch);
        $result = $propPatch->commit();
        $this->assertTrue($result);
    }

    public function testGetPrincipalUrl()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals('principals/admin', $principal->getPrincipalUrl());
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

        $this->assertEquals($expected, $principal->getAlternateUriSet());
    }

    public function testGetAlternateUriSetEmpty()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, [
            'uri' => 'principals/admin',
        ]);

        $expected = [];

        $this->assertEquals($expected, $principal->getAlternateUriSet());
    }

    public function testGetGroupMemberSet()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals([], $principal->getGroupMemberSet());
    }

    public function testGetGroupMembership()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals([], $principal->getGroupMembership());
    }

    public function testSetGroupMemberSet()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $principal->setGroupMemberSet(['principals/foo']);

        $this->assertEquals([
            'principals/admin' => ['principals/foo'],
        ], $principalBackend->groupMembers);
    }

    public function testGetOwner()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals('principals/admin', $principal->getOwner());
    }

    public function testGetGroup()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertNull($principal->getGroup());
    }

    public function testGetACl()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals([
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ], $principal->getACL());
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testSetACl()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $principal->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertNull($principal->getSupportedPrivilegeSet());
    }
}
