<?php

require_once 'Sabre/CardDAV/Backend/Mock.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_CardDAV_ValidateVCardTest extends PHPUnit_Framework_TestCase {

    protected $server;
    protected $cardBackend;

    function setUp() {

        $addressbooks = array(
            array(
                'id' => 'addressbook1',
                'principaluri' => 'principals/admin',
                'uri' => 'addressbook1',
            )
        );

        $this->cardBackend = new Sabre_CardDAV_Backend_Mock($addressbooks,array());
        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();

        $tree = array(
            new Sabre_CardDAV_AddressBookRoot($principalBackend, $this->cardBackend),
        );

        $this->server = new Sabre_DAV_Server($tree);
        $this->server->debugExceptions = true;

        $plugin = new Sabre_CardDAV_Plugin();
        $this->server->addPlugin($plugin);

        $response = new Sabre_HTTP_ResponseMock();
        $this->server->httpResponse = $response;

    }

    function request(Sabre_HTTP_Request $request) {

        $this->server->httpRequest = $request;
        $this->server->exec();

        return $this->server->httpResponse;

    }

    function testCreateFile() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 415 Unsupported Media Type', $response->status);

    }

    function testCreateFileValid() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $request->setBody("BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 201 Created', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);
        $expected = array(
            'uri'          => 'blabla.vcf',
            'carddata' => "BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n",
        );

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1','blabla.vcf'));

    }

    function testCreateFileNoUID() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $request->setBody("BEGIN:VCARD\r\nEND:VCARD\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 400 Bad request', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }


    function testCreateFileVCalendar() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 415 Unsupported Media Type', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testUpdateFile() {

        $this->cardBackend->createCard('addressbook1','blabla.vcf','foo');
        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 415 Unsupported Media Type', $response->status);

    }

    function testUpdateFileParsableBody() {

        $this->cardBackend->createCard('addressbook1','blabla.vcf','foo');
        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $body = "BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n";
        $request->setBody($body);

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 204 No Content', $response->status);

        $expected = array(
            'uri'          => 'blabla.vcf',
            'carddata' => $body,
        );

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1','blabla.vcf'));

    }
}

?>
