<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\HTTP;

class MultiGetTest extends AbstractPluginTestCase
{
    public function testMultiGet()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1',
        ]);

        $request->setBody(
'<?xml version="1.0"?>
<c:addressbook-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
      <c:address-data />
    </d:prop>
    <d:href>/addressbooks/user1/book1/card1</d:href>
</c:addressbook-multiget>'
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
                    '{urn:ietf:params:xml:ns:carddav}address-data' => "BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD",
                ],
            ],
        ], $result);
    }

    public function testMultiGetVCard4()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1',
        ]);

        $request->setBody(
'<?xml version="1.0"?>
<c:addressbook-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
      <c:address-data content-type="text/vcard" version="4.0" />
    </d:prop>
    <d:href>/addressbooks/user1/book1/card1</d:href>
</c:addressbook-multiget>'
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

        $prodId = 'PRODID:-//Sabre//Sabre VObject '.\Sabre\VObject\Version::VERSION.'//EN';

        self::assertEquals([
            '/addressbooks/user1/book1/card1' => [
                200 => [
                    '{DAV:}getetag' => '"'.md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD").'"',
                    '{urn:ietf:params:xml:ns:carddav}address-data' => "BEGIN:VCARD\r\nVERSION:4.0\r\n$prodId\r\nUID:12345\r\nEND:VCARD\r\n",
                ],
            ],
        ], $result);
    }

    public function testMultiGet404()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1',
        ]);

        $request->setBody(
            '<?xml version="1.0"?>
<c:addressbook-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
      <c:address-data />
    </d:prop>
    <d:href>/addressbooks/user1/unknown/card1</d:href>
    <d:href>/addressbooks/user1/book1/card1</d:href>
    <d:href>/addressbooks/user1/book1/unknown-card</d:href>
</c:addressbook-multiget>'
        );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $this->assertEquals(207, $response->status, 'Incorrect status code. Full response body:'.$response->body);

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav">
 <d:response>
  <d:href>/addressbooks/user1/unknown/card1</d:href>
  <d:status>HTTP/1.1 404 Not Found</d:status>
 </d:response>
 <d:response>
  <d:href>/addressbooks/user1/book1/card1</d:href>
    <d:propstat>
      <d:prop>
        <d:getetag>"ffe3b42186ba156c84fc1581c273f01c"</d:getetag>
        <card:address-data>BEGIN:VCARD
VERSION:3.0
UID:12345
END:VCARD</card:address-data>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
 </d:response>
 <d:response>
  <d:href>/addressbooks/user1/book1/unknown-card</d:href>
  <d:status>HTTP/1.1 404 Not Found</d:status>
 </d:response>
</d:multistatus>', $response->body);
    }
}
