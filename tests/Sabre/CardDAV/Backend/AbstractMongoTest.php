<?php

declare(strict_types=1);

namespace Sabre\CardDAV\Backend;

use Sabre\CardDAV;
use Sabre\DAV\PropPatch;

abstract class AbstractMongoTest extends \PHPUnit\Framework\TestCase
{
    use \Sabre\DAV\MongoTestHelperTrait;

    /**
     * @var CardDAV\Backend\Mongo
     */
    protected $backend;

    public function setUp()
    {
        $this->db = $this->getMongo();
        $this->db->drop();
        $this->backend = new Mongo($this->db);

        $book = [
            'principaluri' => 'principals/user1',
            'displayname' => 'book1',
            'uri' => 'book1',
            'description' => 'addressbook 1',
            'synctoken' => 1,
        ];
        $insertResultBook = $this->db->addressbooks->insertOne($book);
        $this->bookId = (string) $insertResultBook->getInsertedId();

        $card = [
            'addressbookid' => new \MongoDB\BSON\ObjectId($this->bookId),
            'carddata' => 'card1',
            'uri' => 'card1',
            'lastmodified' => 0,
            'etag' => '"'.md5('card1').'"',
            'size' => 5,
        ];
        $insertResultCard = $this->db->cards->insertOne($card);
        $this->cardId = (string) $insertResultCard->getInsertedId();
    }

    public function testGetAddressBooksForUser()
    {
        $result = $this->backend->getAddressBooksForUser('principals/user1');

        $expected = [
            [
                'id' => $this->bookId,
                'uri' => 'book1',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'book1',
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'addressbook 1',
                '{http://calendarserver.org/ns/}getctag' => 1,
                '{http://sabredav.org/ns}sync-token' => 1,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testUpdateAddressBookInvalidProp()
    {
        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'updated',
            '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'updated',
            '{DAV:}foo' => 'bar',
        ]);

        $this->backend->updateAddressBook($this->bookId, $propPatch);
        $result = $propPatch->commit();

        $this->assertFalse($result);

        $result = $this->backend->getAddressBooksForUser('principals/user1');

        $expected = [
            [
                'id' => $this->bookId,
                'uri' => 'book1',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'book1',
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'addressbook 1',
                '{http://calendarserver.org/ns/}getctag' => 1,
                '{http://sabredav.org/ns}sync-token' => 1,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testUpdateAddressBookNoProps()
    {
        $propPatch = new PropPatch([
        ]);

        $this->backend->updateAddressBook($this->bookId, $propPatch);
        $result = $propPatch->commit();
        $this->assertTrue($result);

        $result = $this->backend->getAddressBooksForUser('principals/user1');

        $expected = [
            [
                'id' => $this->bookId,
                'uri' => 'book1',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'book1',
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'addressbook 1',
                '{http://calendarserver.org/ns/}getctag' => 1,
                '{http://sabredav.org/ns}sync-token' => 1,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testUpdateAddressBookSuccess()
    {
        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'updated',
            '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'updated',
        ]);

        $this->backend->updateAddressBook($this->bookId, $propPatch);
        $result = $propPatch->commit();

        $this->assertTrue($result);

        $result = $this->backend->getAddressBooksForUser('principals/user1');

        $expected = [
            [
                'id' => $this->bookId,
                'uri' => 'book1',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'updated',
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'updated',
                '{http://calendarserver.org/ns/}getctag' => 2,
                '{http://sabredav.org/ns}sync-token' => 2,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testDeleteAddressBook()
    {
        $this->backend->deleteAddressBook($this->bookId);

        $this->assertEquals([], $this->backend->getAddressBooksForUser('principals/user1'));
    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    public function testCreateAddressBookUnsupportedProp()
    {
        $this->backend->createAddressBook('principals/user1', 'book2', [
            '{DAV:}foo' => 'bar',
        ]);
    }

    public function testCreateAddressBookSuccess()
    {
        $bookId2 = $this->backend->createAddressBook('principals/user1', 'book2', [
            '{DAV:}displayname' => 'book2',
            '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'addressbook 2',
        ]);

        $expected = [
            [
                'id' => $this->bookId,
                'uri' => 'book1',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'book1',
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'addressbook 1',
                '{http://calendarserver.org/ns/}getctag' => 1,
                '{http://sabredav.org/ns}sync-token' => 1,
            ],
            [
                'id' => $bookId2,
                'uri' => 'book2',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'book2',
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'addressbook 2',
                '{http://calendarserver.org/ns/}getctag' => 1,
                '{http://sabredav.org/ns}sync-token' => 1,
            ],
        ];
        $result = $this->backend->getAddressBooksForUser('principals/user1');
        $this->assertEquals($expected, $result);
    }

    public function testGetCards()
    {
        $result = $this->backend->getCards($this->bookId);

        $expected = [
            [
                'id' => $this->cardId,
                'uri' => 'card1',
                'lastmodified' => 0,
                'etag' => '"'.md5('card1').'"',
                'size' => 5,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetCard()
    {
        $result = $this->backend->getCard($this->bookId, 'card1');

        $expected = [
            'id' => $this->cardId,
            'uri' => 'card1',
            'carddata' => 'card1',
            'lastmodified' => 0,
            'etag' => '"'.md5('card1').'"',
            'size' => 5,
        ];

        if (is_resource($result['carddata'])) {
            $result['carddata'] = stream_get_contents($result['carddata']);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @depends testGetCard
     */
    public function testCreateCard()
    {
        $result = $this->backend->createCard($this->bookId, 'card2', 'data2');
        $this->assertEquals('"'.md5('data2').'"', $result);
        $result = $this->backend->getCard($this->bookId, 'card2');
        $this->assertEquals('card2', $result['uri']);
        if (is_resource($result['carddata'])) {
            $result['carddata'] = stream_get_contents($result['carddata']);
        }
        $this->assertEquals('data2', $result['carddata']);
    }

    /**
     * @depends testCreateCard
     */
    public function testGetMultiple()
    {
        $result = $this->backend->createCard($this->bookId, 'card2', 'data2');
        $result = $this->backend->createCard($this->bookId, 'card3', 'data3');
        $check = [
            [
                'uri' => 'card1',
                'carddata' => 'card1',
                'lastmodified' => 0,
            ],
            [
                'uri' => 'card2',
                'carddata' => 'data2',
                'lastmodified' => time(),
            ],
            [
                'uri' => 'card3',
                'carddata' => 'data3',
                'lastmodified' => time(),
            ],
        ];

        $result = $this->backend->getMultipleCards($this->bookId, ['card1', 'card2', 'card3']);

        foreach ($check as $index => $node) {
            foreach ($node as $k => $v) {
                $expected = $v;
                $actual = $result[$index][$k];

                switch ($k) {
                    case 'lastmodified':
                        $this->assertInternalType('int', $actual);
                        break;
                    case 'carddata':
                        if (is_resource($actual)) {
                            $actual = stream_get_contents($actual);
                        }
                        // no break intended.
                    default:
                        $this->assertEquals($expected, $actual);
                        break;
                }
            }
        }
    }

    /**
     * @depends testGetCard
     */
    public function testUpdateCard()
    {
        $result = $this->backend->updateCard($this->bookId, 'card1', 'newdata');
        $this->assertEquals('"'.md5('newdata').'"', $result);

        $result = $this->backend->getCard($this->bookId, 'card1');
        if (is_resource($result['carddata'])) {
            $result['carddata'] = stream_get_contents($result['carddata']);
        }
        $this->assertEquals('newdata', $result['carddata']);
    }

    /**
     * @depends testGetCard
     */
    public function testDeleteCard()
    {
        $this->backend->deleteCard($this->bookId, 'card1');
        $result = $this->backend->getCard($this->bookId, 'card1');
        $this->assertFalse($result);
    }

    public function testGetChanges()
    {
        $backend = $this->backend;
        $id = $backend->createAddressBook(
            'principals/user1',
            'bla',
            []
        );
        $result = $backend->getChangesForAddressBook($id, null, 1);

        $this->assertEquals([
            'syncToken' => 1,
            'added' => [],
            'modified' => [],
            'deleted' => [],
        ], $result);

        $currentToken = $result['syncToken'];

        $dummyCard = "BEGIN:VCARD\r\nEND:VCARD\r\n";

        $backend->createCard($id, 'card1.ics', $dummyCard);
        $backend->createCard($id, 'card2.ics', $dummyCard);
        $backend->createCard($id, 'card3.ics', $dummyCard);
        $backend->updateCard($id, 'card1.ics', $dummyCard);
        $backend->deleteCard($id, 'card2.ics');

        $result = $backend->getChangesForAddressBook($id, $currentToken, 1);

        $this->assertEquals([
            'syncToken' => 6,
            'modified' => ['card1.ics'],
            'deleted' => ['card2.ics'],
            'added' => ['card3.ics'],
        ], $result);
    }
}
