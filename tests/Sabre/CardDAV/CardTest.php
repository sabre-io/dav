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

    public function setUp()
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
        $this->assertEquals('card', $result);
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
        $this->assertEquals("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD", $result);
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
        $this->assertEquals('newdata', $result);
    }

    public function testDelete()
    {
        $this->card->delete();
        $this->assertEquals(1, count($this->backend->cards['foo']));
    }

    public function testGetContentType()
    {
        $this->assertEquals('text/vcard; charset=utf-8', $this->card->getContentType());
    }

    public function testGetETag()
    {
        $this->assertEquals('"'.md5('card').'"', $this->card->getETag());
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
        $this->assertEquals('"blabla"', $card->getETag());
    }

    public function testGetLastModified()
    {
        $this->assertEquals(null, $this->card->getLastModified());
    }

    public function testGetSize()
    {
        $this->assertEquals(4, $this->card->getSize());
        $this->assertEquals(4, $this->card->getSize());
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
        $this->assertEquals(4, $card->getSize());
    }

    public function testACLMethods()
    {
        $this->assertEquals('principals/user1', $this->card->getOwner());
        $this->assertNull($this->card->getGroup());
        $this->assertEquals([
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
        $this->assertEquals([
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
        $this->assertNull(
            $this->card->getSupportedPrivilegeSet()
        );
    }
}
