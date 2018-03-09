<?php declare (strict_types=1);

namespace Sabre\DAV\Mount;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer {

    function setUp() {

        parent::setUp();
        $this->server->addPlugin(new Plugin());

    }

    function testPassThrough() {
        $request = new ServerRequest('GET', '/');
        $response = $this->server->handle($request);

        $this->assertEquals(501, $response->getStatusCode(), 'We expected GET to not be implemented for Directories. Response body: ' . $response->getBody()->getContents());

    }

    function testMountResponse() {
        $request = (new ServerRequest('GET', 'http://example.org/?mount', []))->withQueryParams(['mount' => '']);
        $response = $this->server->handle($request);
        $responseBody = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $responseBody);

        $xml = simplexml_load_string($responseBody);
        $this->assertInstanceOf('SimpleXMLElement', $xml, 'Response was not a valid xml document. The list of errors:' . print_r(libxml_get_errors(), true) . '. xml body: ' . $response->getBody()->getContents() . '. What type we got: ' . gettype($xml) . ' class, if object: ' . get_class($xml));

        $xml->registerXPathNamespace('dm', 'http://purl.org/NET/webdav/mount');
        $url = $xml->xpath('//dm:url');
        $this->assertEquals('http://example.org/', (string)$url[0]);

    }

}
