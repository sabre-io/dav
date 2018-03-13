<?php declare (strict_types=1);

namespace Sabre\DAVACL;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class PrincipalSearchPropertySetTest extends \PHPUnit_Framework_TestCase {

    function getServer() {

        $backend = new PrincipalBackend\Mock();

        $dir = new DAV\SimpleCollection('root');
        $principals = new PrincipalCollection($backend);
        $dir->addChild($principals);

        $fakeServer = new DAV\Server($dir, null, null, function() {});;
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $this->assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('acl'));

        return $fakeServer;

    }

    function testDepth1() {

        $xml = '<?xml version="1.0"?>
<d:principal-search-property-set xmlns:d="DAV:" />';
        $request = new ServerRequest('REPORT', '/principals', [
            'Depth' => 1
        ], $xml);

        $server = $this->getServer();
        $response = $server->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

    }

    function testDepthIncorrectXML() {

        $xml = '<?xml version="1.0"?>
<d:principal-search-property-set xmlns:d="DAV:"><d:ohell /></d:principal-search-property-set>';

        $request = new ServerRequest('REPORT', '/principals', ['Depth' => '0'], $xml);

        $server = $this->getServer();
        $response = $server->handle($request);

        $this->assertEquals(400, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

    }

    function testCorrect() {

        $xml = '<?xml version="1.0"?>
<d:principal-search-property-set xmlns:d="DAV:"/>';

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'Depth'     => '0',
            '/principals',
        ];

        $request = new ServerRequest('REPORT', '/principals', ['Depth' => '0'], $xml);

        $server = $this->getServer();
        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $responseBody);
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());


        $check = [
            '/d:principal-search-property-set',
            '/d:principal-search-property-set/d:principal-search-property'                        => 2,
            '/d:principal-search-property-set/d:principal-search-property/d:prop'                 => 2,
            '/d:principal-search-property-set/d:principal-search-property/d:prop/d:displayname'   => 1,
            '/d:principal-search-property-set/d:principal-search-property/d:prop/s:email-address' => 1,
            '/d:principal-search-property-set/d:principal-search-property/d:description'          => 2,
        ];

        $xml = simplexml_load_string($responseBody);
        $xml->registerXPathNamespace('d', 'DAV:');
        $xml->registerXPathNamespace('s', 'http://sabredav.org/ns');
        foreach ($check as $v1 => $v2) {

            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) $count = $v2;

            $this->assertEquals($count, count($result), 'we expected ' . $count . ' appearances of ' . $xpath . ' . We found ' . count($result) . '. Full response body: ' . $responseBody);

        }

    }

}
