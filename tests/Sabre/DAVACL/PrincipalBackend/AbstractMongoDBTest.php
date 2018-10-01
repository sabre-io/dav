<?php

declare(strict_types=1);

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;
use Sabre\DAV\MkCol;

abstract class AbstractMongoDBTest extends \PHPUnit\Framework\TestCase
{
    use DAV\MongoTestHelperTrait;

    const USER_ID = '54313fcc398fef406b0041b6';
    const GROUP_ID = '54313fcc398fef406b0041c6';
    const GROUPMEMBERS_ID = '54313fcc398fef406b0041d6';

    public function setUp()
    {
        $this->db = $this->getMongo();
        $this->db->drop();
        $this->backend = new Mongo($this->db);

        $this->db->principals->insertOne([
            '_id' => new \MongoDB\BSON\ObjectId(self::USER_ID),
            'uri' => 'principals/user',
            'email' => 'user@example.org',
            'displayname' => 'User',
        ]);

        $this->db->principals->insertOne([
            '_id' => new \MongoDB\BSON\ObjectId(self::GROUP_ID),
            'uri' => 'principals/group',
            'email' => 'group@example.org',
            'displayname' => 'Group',
        ]);

        $this->db->groupmembers->insertOne([
            '_id' => new \MongoDB\BSON\ObjectId(self::GROUPMEMBERS_ID),
            'principal_id' => new \MongoDB\BSON\ObjectId(self::GROUP_ID),
            'member_id' => new \MongoDB\BSON\ObjectId(self::USER_ID),
        ]);
    }

    public function testConstruct()
    {
        $mongo = new Mongo($this->db);
        $this->assertTrue($mongo instanceof Mongo);
    }

    public function testGetPrincipalsByPrefix()
    {
        $expected = [
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

        $this->assertEquals($expected, $this->backend->getPrincipalsByPrefix('principals'));
        $this->assertEquals([], $this->backend->getPrincipalsByPrefix('foo'));
    }

    public function testGetPrincipalByPath()
    {
        $expected = [
            '_id' => new \MongoDB\BSON\ObjectId(self::USER_ID),
            'uri' => 'principals/user',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
            '{DAV:}displayname' => 'User',
        ];

        $this->assertEquals($expected, $this->backend->getPrincipalByPath('principals/user'));
        $this->assertEquals(null, $this->backend->getPrincipalByPath('foo'));
    }

    public function testGetGroupMemberSet()
    {
        $expected = ['principals/user'];

        $this->assertEquals($expected, $this->backend->getGroupMemberSet('principals/group'));
    }

    public function testGetGroupMemberSetWithWrongPrincipal()
    {
        $this->expectException(DAV\Exception::class);

        $this->backend->getGroupMemberSet('wrong');
    }

    public function testGetGroupMembership()
    {
        $expected = ['principals/group'];

        $this->assertEquals($expected, $this->backend->getGroupMembership('principals/user'));
    }

    public function testGetGroupMembershipWithWrongPrincipal()
    {
        $this->expectException(DAV\Exception::class);

        $this->backend->getGroupMembership('wrong');
    }

    public function testSetGroupMemberSet()
    {
        // Start situation
        $this->assertEquals(['principals/user'], $this->backend->getGroupMemberSet('principals/group'));

        // Removing all principals
        $this->backend->setGroupMemberSet('principals/group', []);
        $this->assertEquals([], $this->backend->getGroupMemberSet('principals/group'));

        // // Adding principals again
        $this->backend->setGroupMemberSet('principals/group', ['principals/user']);
        $this->assertEquals(['principals/user'], $this->backend->getGroupMemberSet('principals/group'));
    }

    public function testSearchPrincipals()
    {
        $result = $this->backend->searchPrincipals('principals', []);
        $this->assertEquals([], $result);

        $result = $this->backend->searchPrincipals('principals', ['{DAV:}blabla' => 'foo']);
        $this->assertEquals([], $result);

        $result = $this->backend->searchPrincipals('principals', ['{DAV:}displayname' => 'ou']);
        $this->assertEquals(['principals/group'], $result);

        $result = $this->backend->searchPrincipals('principals', ['{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE']);
        $this->assertEquals(['principals/user'], $result);

        $result = $this->backend->searchPrincipals('mom', ['{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE']);
        $this->assertEquals([], $result);
    }

    public function testUpdatePrincipal()
    {
        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
        ]);

        $this->backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        $this->assertTrue($result);

        $this->assertEquals([
            '_id' => new \MongoDB\BSON\ObjectId(self::USER_ID),
            'uri' => 'principals/user',
            '{DAV:}displayname' => 'pietje',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ], $this->backend->getPrincipalByPath('principals/user'));
    }

    public function testUpdatePrincipalUnknownField()
    {
        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
            '{DAV:}unknown' => 'foo',
        ]);

        $this->backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        $this->assertFalse($result);

        $this->assertEquals([
            '{DAV:}displayname' => 424,
            '{DAV:}unknown' => 403,
        ], $propPatch->getResult());

        $this->assertEquals([
            '_id' => new \MongoDB\BSON\ObjectId(self::USER_ID),
            'uri' => 'principals/user',
            '{DAV:}displayname' => 'User',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ], $this->backend->getPrincipalByPath('principals/user'));
    }

    public function testFindByUriUnknownScheme()
    {
        $this->assertNull($this->backend->findByUri('http://foo', 'principals'));
    }

    public function testFindByUri()
    {
        $this->assertEquals(
            'principals/user',
            $this->backend->findByUri('mailto:user@example.org', 'principals')
        );
    }

    public function testCreatePrincipal()
    {
        $mkCol = new MkCol([], ['{DAV:}displayname' => 'testPrincipal', '{http://sabredav.org/ns}email-address' => 'user36@example.org']);

        $this->backend->createPrincipal('principals/user36', $mkCol);
        $result = $mkCol->commit();
        $principal = $this->backend->getPrincipalByPath('principals/user36');

        $this->assertTrue($result);
        $this->assertEquals([
            '_id' => $principal['_id'],
            'uri' => 'principals/user36',
            '{DAV:}displayname' => 'testPrincipal',
            '{http://sabredav.org/ns}email-address' => 'user36@example.org',
        ], $principal);
    }
}
