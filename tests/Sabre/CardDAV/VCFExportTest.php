<?php

class Sabre_CardDAV_VCFExportTest extends Sabre_DAVServerTest {

    protected $setupCardDAV = true;
    protected $autoLogin = 'user1';
    protected $setupACL = true;

    protected $carddavAddressBooks = array(
        array(
            'id' => 'book1',
            'uri' => 'book1',
            'principaluri' => 'principals/user1',
        )
    );
    protected $carddavCards = array(
        'book1' => array(
            "card1" => "BEGIN:VCARD\r\nFN:Person1\r\nEND:VCARD\r\n",
            "card2" => "BEGIN:VCARD\r\nFN:Person2\r\nEND:VCARD",
            "card3" => "BEGIN:VCARD\r\nFN:Person3\r\nEND:VCARD\r\n",
            "card4" => "BEGIN:VCARD\nFN:Person4\nEND:VCARD\n",
        )
    );

    function setUp() {

        parent::setUp();
        $this->server->addPlugin(
            new Sabre_CardDAV_VCFExportPlugin()
        );

    }

    function testSimple() {

        $this->assertInstanceOf('Sabre_CardDAV_VCFExportPlugin', $this->server->getPlugin('Sabre_CardDAV_VCFExportPlugin'));

    }

    function testExport() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_URI' => '/addressbooks/user1/book1?export',
            'QUERY_STRING' => 'export',
            'REQUEST_METHOD' => 'GET',
        ));

        $response = $this->request($request);
        $this->assertEquals('HTTP/1.1 200 OK', $response->status, $response->body);

        $expected = "BEGIN:VCARD
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
";
        // We actually expected windows line endings
        $expected = str_replace("\n","\r\n", $expected);

        $this->assertEquals($expected, $response->body);

    }

}
