<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV\PropPatch;

class AddressBookTest extends \PHPUnit\Framework\TestCase
{
    use \Sabre\DAV\DbTestHelperTrait;

    /**
     * @var Sabre\CardDAV\AddressBook
     */
    protected $ab;
    protected $backend;

    public function setup(): void
    {
        $this->backend = new Backend\Mock();
        $this->ab = new AddressBook(
            $this->backend,
            [
                'uri' => 'book1',
                'id' => 'foo',
                '{DAV:}displayname' => 'd-name',
                'principaluri' => 'principals/user1',
            ]
        );
    }

    public function testGetName()
    {
        self::assertEquals('book1', $this->ab->getName());
    }

    public function testGetChild()
    {
        $card = $this->ab->getChild('card1');
        self::assertInstanceOf(\Sabre\CardDAV\Card::class, $card);
        self::assertEquals('card1', $card->getName());
    }

    public function testGetChildNotFound()
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);
        $card = $this->ab->getChild('card3');
    }

    public function testGetChildren()
    {
        $cards = $this->ab->getChildren();
        self::assertEquals(2, count($cards));

        self::assertEquals('card1', $cards[0]->getName());
        self::assertEquals('card2', $cards[1]->getName());
    }

    public function testCreateDirectory()
    {
        $this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
        $this->ab->createDirectory('name');
    }

    public function testCreateFile()
    {
        $file = fopen('php://memory', 'r+');
        fwrite($file, 'foo');
        rewind($file);
        $this->ab->createFile('card2', $file);

        self::assertEquals('foo', $this->backend->cards['foo']['card2']);
    }

    public function testDelete()
    {
        $this->ab->delete();
        self::assertEquals(1, count($this->backend->addressBooks));
    }

    public function testSetName()
    {
        $this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
        $this->ab->setName('foo');
    }

    public function testGetLastModified()
    {
        self::assertNull($this->ab->getLastModified());
    }

    public function testUpdateProperties()
    {
        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'barrr',
        ]);
        $this->ab->propPatch($propPatch);
        self::assertTrue($propPatch->commit());

        self::assertEquals('barrr', $this->backend->addressBooks[0]['{DAV:}displayname']);
    }

    public function testGetProperties()
    {
        $props = $this->ab->getProperties(['{DAV:}displayname']);
        self::assertEquals([
            '{DAV:}displayname' => 'd-name',
        ], $props);
    }

    public function testACLMethods()
    {
        self::assertEquals('principals/user1', $this->ab->getOwner());
        self::assertNull($this->ab->getGroup());
        self::assertEquals([
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ], $this->ab->getACL());
    }

    public function testSetACL()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $this->ab->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        self::assertNull(
            $this->ab->getSupportedPrivilegeSet()
        );
    }

    public function testGetSyncTokenNoSyncSupport()
    {
        self::assertNull($this->ab->getSyncToken());
    }

    public function testGetChangesNoSyncSupport()
    {
        self::assertNull($this->ab->getChanges(1, null));
    }

    public function testGetSyncToken()
    {
        $this->driver = 'sqlite';
        $this->dropTables(['addressbooks', 'cards', 'addressbookchanges']);
        $this->createSchema('addressbooks');
        $backend = new Backend\PDO(
            $this->getPDO()
        );
        $ab = new AddressBook($backend, ['id' => 1, '{DAV:}sync-token' => 2]);
        self::assertEquals(2, $ab->getSyncToken());
    }

    public function testGetSyncToken2()
    {
        $this->driver = 'sqlite';
        $this->dropTables(['addressbooks', 'cards', 'addressbookchanges']);
        $this->createSchema('addressbooks');
        $backend = new Backend\PDO(
            $this->getPDO()
        );
        $ab = new AddressBook($backend, ['id' => 1, '{http://sabredav.org/ns}sync-token' => 2]);
        self::assertEquals(2, $ab->getSyncToken());
    }
}
