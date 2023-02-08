<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

class CardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Sabre\CardDAV\Card
     */
    protected $card;
    /**
     * @var Sabre\CardDAV\MockBackend
     */
    protected $backend;

    public function setup(): void
    {
        $this->backend = new Backend\Mock();
        $this->card = new Card(
            $this->backend,
            [
                'uri' => 'book1',
                'id' => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri' => 'card1',
                'addressbookid' => 'foo',
                'carddata' => 'card',
            ]
        );
    }

    public function testGet()
    {
        $result = $this->card->get();
        self::assertEquals('card', $result);
    }

    public function testGet2()
    {
        $this->card = new Card(
            $this->backend,
            [
                'uri' => 'book1',
                'id' => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri' => 'card1',
                'addressbookid' => 'foo',
            ]
        );
        $result = $this->card->get();
        self::assertEquals("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD", $result);
    }

    /**
     * @depends testGet
     */
    public function testPut()
    {
        $file = fopen('php://memory', 'r+');
        fwrite($file, 'newdata');
        rewind($file);
        $this->card->put($file);
        $result = $this->card->get();
        self::assertEquals('newdata', $result);
    }

    public function testDelete()
    {
        $this->card->delete();
        self::assertEquals(1, count($this->backend->cards['foo']));
    }

    public function testGetContentType()
    {
        self::assertEquals('text/vcard; charset=utf-8', $this->card->getContentType());
    }

    public function testGetETag()
    {
        self::assertEquals('"'.md5('card').'"', $this->card->getETag());
    }

    public function testGetETag2()
    {
        $card = new Card(
            $this->backend,
            [
                'uri' => 'book1',
                'id' => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri' => 'card1',
                'addressbookid' => 'foo',
                'carddata' => 'card',
                'etag' => '"blabla"',
            ]
        );
        self::assertEquals('"blabla"', $card->getETag());
    }

    public function testGetLastModified()
    {
        self::assertEquals(null, $this->card->getLastModified());
    }

    public function testGetSize()
    {
        self::assertEquals(4, $this->card->getSize());
        self::assertEquals(4, $this->card->getSize());
    }

    public function testGetSize2()
    {
        $card = new Card(
            $this->backend,
            [
                'uri' => 'book1',
                'id' => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri' => 'card1',
                'addressbookid' => 'foo',
                'etag' => '"blabla"',
                'size' => 4,
            ]
        );
        self::assertEquals(4, $card->getSize());
    }

    public function testACLMethods()
    {
        self::assertEquals('principals/user1', $this->card->getOwner());
        self::assertNull($this->card->getGroup());
        self::assertEquals([
            [
                'privilege' => '{DAV:}all',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
        ], $this->card->getACL());
    }

    public function testOverrideACL()
    {
        $card = new Card(
            $this->backend,
            [
                'uri' => 'book1',
                'id' => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri' => 'card1',
                'addressbookid' => 'foo',
                'carddata' => 'card',
                'acl' => [
                    [
                        'privilege' => '{DAV:}read',
                        'principal' => 'principals/user1',
                        'protected' => true,
                    ],
                ],
            ]
        );
        self::assertEquals([
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
        ], $card->getACL());
    }

    public function testSetACL()
    {
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $this->card->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        self::assertNull(
            $this->card->getSupportedPrivilegeSet()
        );
    }
}
