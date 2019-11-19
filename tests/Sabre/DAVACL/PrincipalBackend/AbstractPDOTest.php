<?php

declare(strict_types=1);

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;

abstract class AbstractPDOTest extends \PHPUnit\Framework\TestCase
{
    use DAV\DbTestHelperTrait;

    public function setUp()
    {
        $this->dropTables(['principals', 'groupmembers']);
        $this->createSchema('principals');

        $pdo = $this->getPDO();

        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/user','user@example.org','User')");
        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/group','group@example.org','Group')");

        $pdo->query('INSERT INTO groupmembers (principal_id,member_id) VALUES (5,4)');
    }

    public function testConstruct()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertTrue($backend instanceof PDO);
    }

    /**
     * @depends testConstruct
     */
    public function testGetPrincipalsByPrefix()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $expected = [
            [
                'uri' => 'principals/admin',
                '{http://sabredav.org/ns}email-address' => 'admin@example.org',
                '{DAV:}displayname' => 'Administrator',
            ],
            [
                'uri' => 'principals/user',
                '{http://sabredav.org/ns}email-address' => 'user@example.org',
                '{DAV:}displayname' => 'User',
            ],
            [
                'uri' => 'principals/group',
                '{http://sabredav.org/ns}email-address' => 'group@example.org',
                '{DAV:}displayname' => 'Group',
            ],
        ];

        $this->assertEquals($expected, $backend->getPrincipalsByPrefix('principals'));
        $this->assertEquals([], $backend->getPrincipalsByPrefix('foo'));
    }

    /**
     * @depends testConstruct
     */
    public function testGetPrincipalByPath()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $expected = [
            'id' => 4,
            'uri' => 'principals/user',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
            '{DAV:}displayname' => 'User',
        ];

        $this->assertEquals($expected, $backend->getPrincipalByPath('principals/user'));
        $this->assertEquals(null, $backend->getPrincipalByPath('foo'));
    }

    public function testGetGroupMemberSet()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $expected = ['principals/user'];

        $this->assertEquals($expected, $backend->getGroupMemberSet('principals/group'));
    }

    public function testGetGroupMembership()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $expected = ['principals/group'];

        $this->assertEquals($expected, $backend->getGroupMembership('principals/user'));
    }

    public function testSetGroupMemberSet()
    {
        $pdo = $this->getPDO();

        // Start situation
        $backend = new PDO($pdo);
        $this->assertEquals(['principals/user'], $backend->getGroupMemberSet('principals/group'));

        // Removing all principals
        $backend->setGroupMemberSet('principals/group', []);
        $this->assertEquals([], $backend->getGroupMemberSet('principals/group'));

        // Adding principals again
        $backend->setGroupMemberSet('principals/group', ['principals/user']);
        $this->assertEquals(['principals/user'], $backend->getGroupMemberSet('principals/group'));
    }

    public function testSearchPrincipals()
    {
        $pdo = $this->getPDO();

        $backend = new PDO($pdo);

        $result = $backend->searchPrincipals('principals', ['{DAV:}blabla' => 'foo']);
        $this->assertEquals([], $result);

        $result = $backend->searchPrincipals('principals', ['{DAV:}displayname' => 'ou']);
        $this->assertEquals(['principals/group'], $result);

        $result = $backend->searchPrincipals('principals', ['{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE']);
        $this->assertEquals(['principals/user'], $result);

        $result = $backend->searchPrincipals('mom', ['{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE']);
        $this->assertEquals([], $result);
    }

    public function testUpdatePrincipal()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
        ]);

        $backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        $this->assertTrue($result);

        $this->assertEquals([
            'id' => 4,
            'uri' => 'principals/user',
            '{DAV:}displayname' => 'pietje',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ], $backend->getPrincipalByPath('principals/user'));
    }

    public function testUpdatePrincipalUnknownField()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
            '{DAV:}unknown' => 'foo',
        ]);

        $backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        $this->assertFalse($result);

        $this->assertEquals([
            '{DAV:}displayname' => 424,
            '{DAV:}unknown' => 403,
        ], $propPatch->getResult());

        $this->assertEquals([
            'id' => '4',
            'uri' => 'principals/user',
            '{DAV:}displayname' => 'User',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ], $backend->getPrincipalByPath('principals/user'));
    }

    public function testFindByUriUnknownScheme()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertNull($backend->findByUri('http://foo', 'principals'));
    }

    public function testFindByUriWithMailtoAddress()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertEquals(
            'principals/user',
            $backend->findByUri('mailto:user@example.org', 'principals')
        );
    }

    public function testFindByUriWithUri()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertEquals(
            'principals/user',
            $backend->findByUri('principals/user', 'principals')
        );
    }

    public function testFindByUriWithUnknownUri()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertNull($backend->findByUri('principals/other', 'principals'));
    }
}
