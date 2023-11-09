<?php

declare(strict_types=1);

namespace Sabre\DAVACL\PrincipalBackend;

use PHPUnit\Framework\TestCase;
use Sabre\DAV;

abstract class AbstractPDOTestCase extends TestCase
{
    use DAV\DbTestHelperTrait;

    public function setup(): void
    {
        $this->dropTables(['principals', 'groupmembers']);
        $this->createSchema('principals');

        $pdo = $this->getPDO();

        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/user','user@example.org','User')");
        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/group','group@example.org','Group')");

        $pdo->query('INSERT INTO groupmembers (principal_id,member_id) VALUES (5,4)');
    }

    public function testConstruct(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        self::assertInstanceOf(PDO::class, $backend);
    }

    /**
     * @depends testConstruct
     */
    public function testGetPrincipalsByPrefix(): void
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

        self::assertEquals($expected, $backend->getPrincipalsByPrefix('principals'));
        self::assertEquals([], $backend->getPrincipalsByPrefix('foo'));
    }

    /**
     * @depends testConstruct
     */
    public function testGetPrincipalByPath(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $expected = [
            'id' => 4,
            'uri' => 'principals/user',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
            '{DAV:}displayname' => 'User',
        ];

        self::assertEquals($expected, $backend->getPrincipalByPath('principals/user'));
        self::assertEquals(null, $backend->getPrincipalByPath('foo'));
    }

    public function testGetGroupMemberSet(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $expected = ['principals/user'];

        self::assertEquals($expected, $backend->getGroupMemberSet('principals/group'));
    }

    public function testGetGroupMembership(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $expected = ['principals/group'];

        self::assertEquals($expected, $backend->getGroupMembership('principals/user'));
    }

    public function testSetGroupMemberSet(): void
    {
        $pdo = $this->getPDO();

        // Start situation
        $backend = new PDO($pdo);
        self::assertEquals(['principals/user'], $backend->getGroupMemberSet('principals/group'));

        // Removing all principals
        $backend->setGroupMemberSet('principals/group', []);
        self::assertEquals([], $backend->getGroupMemberSet('principals/group'));

        // Adding principals again
        $backend->setGroupMemberSet('principals/group', ['principals/user']);
        self::assertEquals(['principals/user'], $backend->getGroupMemberSet('principals/group'));
    }

    public function testSearchPrincipals(): void
    {
        $pdo = $this->getPDO();

        $backend = new PDO($pdo);

        $result = $backend->searchPrincipals('principals', ['{DAV:}blabla' => 'foo']);
        self::assertEquals([], $result);

        $result = $backend->searchPrincipals('principals', ['{DAV:}displayname' => 'ou']);
        self::assertEquals(['principals/group'], $result);

        $result = $backend->searchPrincipals('principals', ['{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE']);
        self::assertEquals(['principals/user'], $result);

        $result = $backend->searchPrincipals('mom', ['{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE']);
        self::assertEquals([], $result);
    }

    public function testUpdatePrincipal(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
        ]);

        $backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        self::assertTrue($result);

        self::assertEquals([
            'id' => 4,
            'uri' => 'principals/user',
            '{DAV:}displayname' => 'pietje',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ], $backend->getPrincipalByPath('principals/user'));
    }

    public function testUpdatePrincipalUnknownField(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
            '{DAV:}unknown' => 'foo',
        ]);

        $backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        self::assertFalse($result);

        self::assertEquals([
            '{DAV:}displayname' => 424,
            '{DAV:}unknown' => 403,
        ], $propPatch->getResult());

        self::assertEquals([
            'id' => '4',
            'uri' => 'principals/user',
            '{DAV:}displayname' => 'User',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ], $backend->getPrincipalByPath('principals/user'));
    }

    public function testFindByUriUnknownScheme(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        self::assertNull($backend->findByUri('http://foo', 'principals'));
    }

    public function testFindByUriWithMailtoAddress(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        self::assertEquals(
            'principals/user',
            $backend->findByUri('mailto:user@example.org', 'principals')
        );
    }

    public function testFindByUriWithUri(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        self::assertEquals(
            'principals/user',
            $backend->findByUri('principals/user', 'principals')
        );
    }

    public function testFindByUriWithUnknownUri(): void
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        self::assertNull($backend->findByUri('principals/other', 'principals'));
    }
}
