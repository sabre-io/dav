<?php declare (strict_types=1);

namespace Sabre\DAV\Mount;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer {

    function setUp() {

        parent::setUp();
        $this->server->addPlugin(new Plugin());

    }

    function testPassThrough() {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'GET',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals(501, $this->getResponse()->getStatusCode(), 'We expected GET to not be implemented for Directories. Response body: ' . $this->getResponse()->getBody()->getContents());

    }

    function testMountResponse() {

        $serverVars = [
            'REQUEST_URI'    => '/?mount',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING'   => 'mount',
            'HTTP_HOST'      => 'example.org',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $xml = simplexml_load_string($this->getResponse()->getBody()->getContents());
        $this->assertInstanceOf('SimpleXMLElement', $xml, 'Response was not a valid xml document. The list of errors:' . print_r(libxml_get_errors(), true) . '. xml body: ' . $this->getResponse()->getBody()->getContents() . '. What type we got: ' . gettype($xml) . ' class, if object: ' . get_class($xml));

        $xml->registerXPathNamespace('dm', 'http://purl.org/NET/webdav/mount');
        $url = $xml->xpath('//dm:url');
        $this->assertEquals('http://example.org/', (string)$url[0]);

    }

}
