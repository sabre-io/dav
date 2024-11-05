<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\HTTP;

class AddressBookQueryTest extends AbstractPluginTestCase
{
    public function testQuery()
    {
        $request = new HTTP\Request(
            'REPORT',
            '/addressbooks/user1/book1',
            ['Depth' => '1']
        );

        $request->setBody(
            '<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="uid" />
    </c:filter>
</c:addressbook-query>'
        );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $bodyAsString = $response->getBodyAsString();
        self::assertEquals(207, $response->status, 'Incorrect status code. Full response body:'.$bodyAsString);

        // using the client for parsing
        $client = new DAV\Client(['baseUri' => '/']);

        $result = $client->parseMultiStatus($bodyAsString);

        self::assertEquals([
            '/addressbooks/user1/book1/card1' => [
                200 => [
                    '{DAV:}getetag' => '"'.md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD").'"',
                ],
             ],
            '/addressbooks/user1/book1/card2' => [
                404 => [
                    '{DAV:}getetag' => null,
                ],
            ],
        ], $result);
    }

    public function testQueryDepth0()
    {
        $request = new HTTP\Request(
            'REPORT',
            '/addressbooks/user1/book1/card1',
            ['Depth' => '0']
        );

        $request->setBody(
            '<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="uid" />
    </c:filter>
</c:addressbook-query>'
        );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $bodyAsString = $response->getBodyAsString();
        self::assertEquals(207, $response->status, 'Incorrect status code. Full response body:'.$bodyAsString);

        // using the client for parsing
        $client = new DAV\Client(['baseUri' => '/']);

        $result = $client->parseMultiStatus($bodyAsString);

        self::assertEquals([
            '/addressbooks/user1/book1/card1' => [
                200 => [
                    '{DAV:}getetag' => '"'.md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD").'"',
                ],
             ],
        ], $result);
    }

    public function testQueryNoMatch()
    {
        $request = new HTTP\Request(
            'REPORT',
            '/addressbooks/user1/book1',
            ['Depth' => '1']
        );

        $request->setBody(
            '<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="email" />
    </c:filter>
</c:addressbook-query>'
        );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $bodyAsString = $response->getBodyAsString();
        self::assertEquals(207, $response->status, 'Incorrect status code. Full response body:'.$bodyAsString);

        // using the client for parsing
        $client = new DAV\Client(['baseUri' => '/']);

        $result = $client->parseMultiStatus($bodyAsString);

        self::assertEquals([], $result);
    }

    public function testQueryLimit()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1',
            'HTTP_DEPTH' => '1',
        ]);

        $request->setBody(
            '<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="uid" />
    </c:filter>
    <c:limit><c:nresults>1</c:nresults></c:limit>
</c:addressbook-query>'
        );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $bodyAsString = $response->getBodyAsString();
        self::assertEquals(207, $response->status, 'Incorrect status code. Full response body:'.$bodyAsString);

        // using the client for parsing
        $client = new DAV\Client(['baseUri' => '/']);

        $result = $client->parseMultiStatus($bodyAsString);

        self::assertEquals([
            '/addressbooks/user1/book1/card1' => [
                200 => [
                    '{DAV:}getetag' => '"'.md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD").'"',
                ],
             ],
        ], $result);
    }

    public function testJson()
    {
        $request = new HTTP\Request(
            'REPORT',
            '/addressbooks/user1/book1/card1',
            ['Depth' => '0']
        );

        $request->setBody(
            '<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <c:address-data content-type="application/vcard+json" />
      <d:getetag />
    </d:prop>
</c:addressbook-query>'
        );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $bodyAsString = $response->getBodyAsString();
        self::assertEquals(207, $response->status, 'Incorrect status code. Full response body:'.$bodyAsString);

        // using the client for parsing
        $client = new DAV\Client(['baseUri' => '/']);

        $result = $client->parseMultiStatus($bodyAsString);

        $vobjVersion = \Sabre\VObject\Version::VERSION;

        self::assertEquals([
            '/addressbooks/user1/book1/card1' => [
                200 => [
                    '{DAV:}getetag' => '"'.md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD").'"',
                    '{urn:ietf:params:xml:ns:carddav}address-data' => '["vcard",[["version",{},"text","4.0"],["prodid",{},"text","-\/\/Sabre\/\/Sabre VObject '.$vobjVersion.'\/\/EN"],["uid",{},"text","12345"]]]',
                ],
             ],
        ], $result);
    }

    public function testVCard4()
    {
        $request = new HTTP\Request(
            'REPORT',
            '/addressbooks/user1/book1/card1',
            ['Depth' => '0']
        );

        $request->setBody(
            '<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <c:address-data content-type="text/vcard" version="4.0" />
      <d:getetag />
    </d:prop>
</c:addressbook-query>'
        );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $bodyAsString = $response->getBodyAsString();
        self::assertEquals(207, $response->status, 'Incorrect status code. Full response body:'.$bodyAsString);

        // using the client for parsing
        $client = new DAV\Client(['baseUri' => '/']);

        $result = $client->parseMultiStatus($bodyAsString);

        $vobjVersion = \Sabre\VObject\Version::VERSION;

        self::assertEquals([
            '/addressbooks/user1/book1/card1' => [
                200 => [
                    '{DAV:}getetag' => '"'.md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD").'"',
                    '{urn:ietf:params:xml:ns:carddav}address-data' => "BEGIN:VCARD\r\nVERSION:4.0\r\nPRODID:-//Sabre//Sabre VObject $vobjVersion//EN\r\nUID:12345\r\nEND:VCARD\r\n",
                ],
             ],
        ], $result);
    }

    public function testAddressBookDepth0()
    {
        $request = new HTTP\Request(
            'REPORT',
            '/addressbooks/user1/book1',
            ['Depth' => '0']
        );

        $request->setBody(
            '<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <c:address-data content-type="application/vcard+json" />
      <d:getetag />
    </d:prop>
</c:addressbook-query>'
        );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        self::assertEquals(415, $response->status, 'Incorrect status code. Full response body:'.$response->getBodyAsString());
    }

    public function testAddressBookProperties()
    {
        $request = new HTTP\Request(
            'REPORT',
            '/addressbooks/user1/book3',
            ['Depth' => '1']
        );

        $request->setBody(
            '<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <c:address-data>
          <c:prop name="FN"/>
          <c:prop name="BDAY"/>
      </c:address-data>
      <d:getetag />
    </d:prop>
</c:addressbook-query>'
        );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $bodyAsString = $response->getBodyAsString();
        self::assertEquals(207, $response->status, 'Incorrect status code. Full response body:'.$bodyAsString);

        // using the client for parsing
        $client = new DAV\Client(['baseUri' => '/']);

        $result = $client->parseMultiStatus($bodyAsString);

        self::assertEquals([
            '/addressbooks/user1/book3/card3' => [
                200 => [
                    '{DAV:}getetag' => '"'.md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nFN:Test-Card\nEMAIL;TYPE=home:bar@example.org\nEND:VCARD").'"',
                    '{urn:ietf:params:xml:ns:carddav}address-data' => "BEGIN:VCARD\r\nVERSION:3.0\r\nUID:12345\r\nFN:Test-Card\r\nEND:VCARD\r\n",
                ],
            ],
        ], $result);
    }
}
