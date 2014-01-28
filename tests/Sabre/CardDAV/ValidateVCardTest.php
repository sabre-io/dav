<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\HTTP;
use Sabre\DAVACL;

require_once 'Sabre/HTTP/ResponseMock.php';

class ValidateVCardTest extends \PHPUnit_Framework_TestCase
{
    protected $server;
    protected $cardBackend;

    public function setUp()
    {
        $addressbooks = array(
            array(
                'id' => 'addressbook1',
                'principaluri' => 'principals/admin',
                'uri' => 'addressbook1',
            )
        );

        $this->cardBackend = new Backend\Mock($addressbooks,array());
        $principalBackend = new DAVACL\PrincipalBackend\Mock();

        $tree = array(
            new AddressBookRoot($principalBackend, $this->cardBackend),
        );

        $this->server = new DAV\Server($tree);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->debugExceptions = true;

        $plugin = new Plugin();
        $this->server->addPlugin($plugin);

        $response = new HTTP\ResponseMock();
        $this->server->httpResponse = $response;
    }

    public function request(HTTP\Request $request)
    {
        $this->server->httpRequest = $request;
        $this->server->exec();

        return $this->server->httpResponse;
    }

    public function testCreateCard()
    {
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));

        $response = $this->request($request);

        $this->assertEquals(415, $response->status);
    }

    public function testCreateCardValid()
    {
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $request->setBody("BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n");

        $response = $this->request($request);

        $this->assertEquals(201, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);
        $expected = array(
            'uri' => 'blabla.vcf',
            'carddata' => "BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n",
        );

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1','blabla.vcf'));
    }

    public function testCreateCardNoUID()
    {
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $request->setBody("BEGIN:VCARD\r\nEND:VCARD\r\n");

        $response = $this->request($request);

        $this->assertEquals(201, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

        $foo = $this->cardBackend->getCard('addressbook1','blabla.vcf');
        $this->assertTrue(strpos($foo['carddata'],'UID') !== false);
    }

    public function testCreateCardWithPost()
    {
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1',
            'CONTENT_TYPE' => 'text/x-vcard'
        ));

        $request->setBody("BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n");

        $response = $this->request($request);

        $this->assertEquals(201, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

        $uri = $response->getHeader('location');
        $etag = $response->getHeader('etag');

        $this->assertNotEmpty($uri);
        $this->assertNotEmpty($etag);

        $expected = array(
            'uri' => $uri,
            'carddata' => "BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n",
        );

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1', $uri));
    }

    public function testCreateCardVCalendar()
    {
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals(415, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);
    }

    public function testUpdateCard()
    {
        $this->cardBackend->createCard('addressbook1','blabla.vcf','foo');
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));

        $response = $this->request($request);

        $this->assertEquals(415, $response->status);
    }

    public function testUpdateCardParsableBody()
    {
        $this->cardBackend->createCard('addressbook1','blabla.vcf','foo');
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $body = "BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n";
        $request->setBody($body);

        $response = $this->request($request);

        $this->assertEquals(204, $response->status);

        $expected = array(
            'uri' => 'blabla.vcf',
            'carddata' => $body,
        );

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1','blabla.vcf'));
    }
}
