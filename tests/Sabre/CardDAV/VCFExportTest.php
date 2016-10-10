<?php

namespace Sabre\CardDAV;

use Sabre\HTTP;

class VCFExportTest extends \Sabre\DAVServerTest {

    protected $setupCardDAV = true;
    protected $autoLogin = 'user1';
    protected $setupACL = true;

    protected $carddavAddressBooks = [
        [
            'id'           => 'book1',
            'uri'          => 'book1',
            'principaluri' => 'principals/user1',
        ]
    ];
    protected $carddavCards = [
        'book1' => [
            'card1' => "BEGIN:VCARD\r\nFN:Person1\r\nEND:VCARD\r\n",
            'card2' => "BEGIN:VCARD\r\nFN:Person2\r\nEND:VCARD",
            'card3' => "BEGIN:VCARD\r\nFN:Person3\r\nEND:VCARD\r\n",
            'card4' => "BEGIN:VCARD\nFN:Person4\nEND:VCARD\n",
        ]
    ];

    function setUp() {

        parent::setUp();
        $plugin = new VCFExportPlugin();
        $this->server->addPlugin(
            $plugin
        );

    }

    function testSimple() {

        $plugin = $this->server->getPlugin('vcf-export');
        $this->assertInstanceOf(VCFExportPlugin::class, $plugin);

        $this->assertEquals(
            'vcf-export',
            $plugin->getPluginInfo()['name']
        );

    }

    function testExport() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI'    => '/addressbooks/user1/book1?export',
            'QUERY_STRING'   => 'export',
            'REQUEST_METHOD' => 'GET',
        ]);

        $response = $this->assertHttpStatus(200, $request);

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

        $this->assertEquals($expected, $response->getBody());

    }

    function testBrowserIntegration() {

        $actions = '';
        $addressbook = new AddressBook($this->carddavBackend, []);
        $this->server->emit('browserButtonActions', ['/foo', $addressbook, &$actions]);
        $this->assertContains('/foo?export', $actions);

    }

    function testContentDisposition() {

        $request = new HTTP\Request(
            'GET',
            '/addressbooks/user1/book1?export'
        );

        $response = $this->assertHttpStatus(200, $request);
        $this->assertEquals('text/directory', $response->getHeader('Content-Type'));
        $this->assertEquals(
            'attachment; filename="book1-' . date('Y-m-d') . '.vcf"',
            $response->getHeader('Content-Disposition')
        );

    }

    function testContentDispositionBadChars() {

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

        $response = $this->assertHttpStatus(200, $request);
        $this->assertEquals('text/directory', $response->getHeader('Content-Type'));
        $this->assertEquals(
            'attachment; filename="book-b_adchars-' . date('Y-m-d') . '.vcf"',
            $response->getHeader('Content-Disposition')
        );

    }

}
