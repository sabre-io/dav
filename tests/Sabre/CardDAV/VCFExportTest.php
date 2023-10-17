<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\HTTP;

class VCFExportTest extends \Sabre\DAVServerTest
{
    protected $setupCardDAV = true;
    protected $autoLogin = 'user1';
    protected $setupACL = true;

    protected $carddavAddressBooks = [
        [
            'id' => 'book1',
            'uri' => 'book1',
            'principaluri' => 'principals/user1',
        ],
    ];
    protected $carddavCards = [
        'book1' => [
            'card1' => "BEGIN:VCARD\r\nFN:Person1\r\nEND:VCARD\r\n",
            'card2' => "BEGIN:VCARD\r\nFN:Person2\r\nEND:VCARD",
            'card3' => "BEGIN:VCARD\r\nFN:Person3\r\nEND:VCARD\r\n",
            'card4' => "BEGIN:VCARD\nFN:Person4\nEND:VCARD\n",
        ],
    ];

    public function setup(): void
    {
        parent::setUp();
        $plugin = new VCFExportPlugin();
        $this->server->addPlugin(
            $plugin
        );
    }

    public function testSimple()
    {
        $plugin = $this->server->getPlugin('vcf-export');
        self::assertInstanceOf('Sabre\\CardDAV\\VCFExportPlugin', $plugin);

        self::assertEquals(
            'vcf-export',
            $plugin->getPluginInfo()['name']
        );
    }

    public function testExport()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/addressbooks/user1/book1?export',
            'QUERY_STRING' => 'export',
            'REQUEST_METHOD' => 'GET',
        ]);

        $response = $this->request($request);
        self::assertEquals(200, $response->status, $response->getBodyAsString());

        $expected = 'BEGIN:VCARD
FN:Person1
END:VCARD
BEGIN:VCARD
FN:Person2
END:VCARD
BEGIN:VCARD
FN:Person3
END:VCARD
BEGIN:VCARD
FN:Person4
END:VCARD
';
        // We actually expected windows line endings
        $expected = str_replace("\n", "\r\n", $expected);

        self::assertEquals($expected, $response->getBodyAsString());
    }

    public function testBrowserIntegration()
    {
        $plugin = $this->server->getPlugin('vcf-export');
        $actions = '';
        $addressbook = new AddressBook($this->carddavBackend, []);
        $this->server->emit('browserButtonActions', ['/foo', $addressbook, &$actions]);
        self::assertStringContainsString('/foo?export', $actions);
    }

    public function testContentDisposition()
    {
        $request = new HTTP\Request(
            'GET',
            '/addressbooks/user1/book1?export'
        );

        $response = $this->request($request, 200);
        self::assertEquals('text/directory', $response->getHeader('Content-Type'));
        self::assertEquals(
            'attachment; filename="book1-'.date('Y-m-d').'.vcf"',
            $response->getHeader('Content-Disposition')
        );
    }

    public function testContentDispositionBadChars()
    {
        $this->carddavBackend->createAddressBook(
            'principals/user1',
            'book-b_ad"(ch)ars',
            []
        );
        $this->carddavBackend->createCard(
            'book-b_ad"(ch)ars',
            'card1',
            "BEGIN:VCARD\r\nFN:Person1\r\nEND:VCARD\r\n"
        );

        $request = new HTTP\Request(
            'GET',
            '/addressbooks/user1/book-b_ad"(ch)ars?export'
        );

        $response = $this->request($request, 200);
        self::assertEquals('text/directory', $response->getHeader('Content-Type'));
        self::assertEquals(
            'attachment; filename="book-b_adchars-'.date('Y-m-d').'.vcf"',
            $response->getHeader('Content-Disposition')
        );
    }
}
