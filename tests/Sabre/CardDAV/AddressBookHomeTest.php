<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV\MkCol;

class AddressBookHomeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Sabre\CardDAV\AddressBookHome
     */
    protected $s;
    protected $backend;

    public function setup(): void
    {
        $this->backend = new Backend\Mock();
        $this->s = new AddressBookHome(
            $this->backend,
            'principals/user1'
        );
    }

    public function testGetName()
    {
        self::assertEquals('user1', $this->s->getName());
    }

    public function testSetName()
    {
        $this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
        $this->s->setName('user2');
    }

    public function testDelete()
    {
        $this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
        $this->s->delete();
    }

    public function testGetLastModified()
    {
        self::assertNull($this->s->getLastModified());
    }

    public function testCreateFile()
    {
        $this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
        $this->s->createFile('bla');
    }

    public function testCreateDirectory()
    {
        $this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
        $this->s->createDirectory('bla');
    }

    public function testGetChild()
    {
        $child = $this->s->getChild('book1');
        self::assertInstanceOf(\Sabre\CardDAV\AddressBook::class, $child);
        self::assertEquals('book1', $child->getName());
    }

    public function testGetChild404()
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);
        $this->s->getChild('book2');
    }

    public function testGetChildren()
    {
        $children = $this->s->getChildren();
        self::assertEquals(2, count($children));
        self::assertInstanceOf(\Sabre\CardDAV\AddressBook::class, $children[0]);
        self::assertEquals('book1', $children[0]->getName());
    }

    public function testCreateExtendedCollection()
    {
        $resourceType = [
            '{'.Plugin::NS_CARDDAV.'}addressbook',
            '{DAV:}collection',
        ];
        $this->s->createExtendedCollection('book2', new MkCol($resourceType, ['{DAV:}displayname' => 'a-book 2']));

        self::assertEquals([
            'id' => 'book2',
            'uri' => 'book2',
            '{DAV:}displayname' => 'a-book 2',
            'principaluri' => 'principals/user1',
        ], $this->backend->addressBooks[2]);
    }

    public function testCreateExtendedCollectionInvalid()
    {
        $this->expectException(\Sabre\DAV\Exception\InvalidResourceType::class);
        $resourceType = [
            '{DAV:}collection',
        ];
        $this->s->createExtendedCollection('book2', new MkCol($resourceType, ['{DAV:}displayname' => 'a-book 2']));
    }

    public function testACLMethods()
    {
        self::assertEquals('principals/user1', $this->s->getOwner());
        self::assertNull($this->s->getGroup());
        self::assertEquals([
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ], $this->s->getACL());
    }

    public function testSetACL()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $this->s->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        self::assertNull(
            $this->s->getSupportedPrivilegeSet()
        );
    }
}
