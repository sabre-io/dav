<?php declare (strict_types=1);

namespace Sabre\CardDAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class MultiGetTest extends AbstractPluginTest {

    function testMultiGet() {

        $request = new ServerRequest('REPORT', '/addressbooks/user1/book1', [], '<?xml version="1.0"?>
<c:addressbook-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
      <c:address-data />
    </d:prop>
    <d:href>/addressbooks/user1/book1/card1</d:href>
</c:addressbook-multiget>'
        );





        $response = $this->server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(207, $response->getStatusCode(), 'Incorrect status code. Full response body:' . $responseBody);

        // using the client for parsing
        $client = new DAV\Client(['baseUri' => '/']);

        $result = $client->parseMultiStatus($responseBody);

        $this->assertEquals([
            '/addressbooks/user1/book1/card1' => [
                200 => [
                    '{DAV:}getetag'                                => '"' . md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD") . '"',
                    '{urn:ietf:params:xml:ns:carddav}address-data' => "BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD",
                ]
            ]
        ], $result);

    }

    function testMultiGetVCard4() {

        $request = new ServerRequest('REPORT', '/addressbooks/user1/book1', [],
'<?xml version="1.0"?>
<c:addressbook-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
      <c:address-data content-type="text/vcard" version="4.0" />
    </d:prop>
    <d:href>/addressbooks/user1/book1/card1</d:href>
</c:addressbook-multiget>'
        );




        $response = $this->server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(207, $response->getStatusCode(), 'Incorrect status code. Full response body:' . $responseBody);

        // using the client for parsing
        $client = new DAV\Client(['baseUri' => '/']);

        $result = $client->parseMultiStatus($responseBody);

        $prodId = "PRODID:-//Sabre//Sabre VObject " . \Sabre\VObject\Version::VERSION . "//EN";

        $this->assertEquals([
            '/addressbooks/user1/book1/card1' => [
                200 => [
                    '{DAV:}getetag'                                => '"' . md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD") . '"',
                    '{urn:ietf:params:xml:ns:carddav}address-data' => "BEGIN:VCARD\r\nVERSION:4.0\r\n$prodId\r\nUID:12345\r\nEND:VCARD\r\n",
                ]
            ]
        ], $result);

    }
}
