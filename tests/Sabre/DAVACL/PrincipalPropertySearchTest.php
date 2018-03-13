<?php declare (strict_types=1);

namespace Sabre\DAVACL;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\DAV\Server;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class PrincipalPropertySearchTest extends \PHPUnit_Framework_TestCase {

    function getServer() {

        $backend = new PrincipalBackend\Mock();

        $dir = new DAV\SimpleCollection('root');
        $principals = new PrincipalCollection($backend);
        $dir->addChild($principals);

        $fakeServer = new Server($dir, null, null, function() {});
        $fakeServer->debugExceptions = true;
        $plugin = new MockPlugin();
        $plugin->allowAccessToNodesWithoutACL = true;
        $plugin->allowUnauthenticatedAccess = false;

        $this->assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('acl'));

        return $fakeServer;

    }

    function testDepth1() {

        $xml = '<?xml version="1.0"?>
<d:principal-property-search xmlns:d="DAV:">
  <d:property-search>
     <d:prop>
       <d:displayname />
     </d:prop>
     <d:match>user</d:match>
  </d:property-search>
  <d:prop>
    <d:displayname />
    <d:getcontentlength />
  </d:prop>
</d:principal-property-search>';

        $request = new ServerRequest('REPORT', '/principals', [
            'Depth' => '1'
        ], $xml);

        $server = $this->getServer();
        $response = $server->handle($request);
        $this->assertEquals(400, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

    }


    function testUnknownSearchField() {

        $xml = '<?xml version="1.0"?>
<d:principal-property-search xmlns:d="DAV:">
  <d:property-search>
     <d:prop>
       <d:yourmom />
     </d:prop>
     <d:match>user</d:match>
  </d:property-search>
  <d:prop>
    <d:displayname />
    <d:getcontentlength />
  </d:prop>
</d:principal-property-search>';

        $request = new ServerRequest('REPort', '/principals', ['Depth' => '0'], $xml);

        $server = $this->getServer();
        

        
        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();

        $this->assertEquals(207, $response->getStatusCode(), "Full body: " . $responseBody);
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            'Vary'            => ['Brief,Prefer'],
        ], $response->getHeaders());

    }

    function testCorrect() {

        $xml = '<?xml version="1.0"?>
<d:principal-property-search xmlns:d="DAV:">
  <d:apply-to-principal-collection-set />
  <d:property-search>
     <d:prop>
       <d:displayname />
     </d:prop>
     <d:match>user</d:match>
  </d:property-search>
  <d:prop>
    <d:displayname />
    <d:getcontentlength />
  </d:prop>
</d:principal-property-search>';

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'Depth'     => '0',
            '/',
        ];

        $request = new ServerRequest('REPORT', '/', ['Depth' => '0'], $xml);

        $server = $this->getServer();
        

        

        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(207, $response->getStatusCode(), $responseBody);
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            'Vary'            => ['Brief,Prefer'],
        ], $response->getHeaders());


        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response'                                      => 2,
            '/d:multistatus/d:response/d:href'                               => 2,
            '/d:multistatus/d:response/d:propstat'                           => 4,
            '/d:multistatus/d:response/d:propstat/d:prop'                    => 4,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname'      => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength' => 2,
            '/d:multistatus/d:response/d:propstat/d:status'                  => 4,
        ];

        $xml = simplexml_load_string($responseBody);
        $xml->registerXPathNamespace('d', 'DAV:');
        foreach ($check as $v1 => $v2) {

            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) $count = $v2;

            $this->assertEquals($count, count($result), 'we expected ' . $count . ' appearances of ' . $xpath . ' . We found ' . count($result) . '. Full response body: ' . $responseBody);

        }

    }

    function testAND() {

        $xml = '<?xml version="1.0"?>
<d:principal-property-search xmlns:d="DAV:">
  <d:apply-to-principal-collection-set />
  <d:property-search>
     <d:prop>
       <d:displayname />
     </d:prop>
     <d:match>user</d:match>
  </d:property-search>
  <d:property-search>
     <d:prop>
       <d:foo />
     </d:prop>
     <d:match>bar</d:match>
  </d:property-search>
  <d:prop>
    <d:displayname />
    <d:getcontentlength />
  </d:prop>
</d:principal-property-search>';
        $request = new ServerRequest('REPORT', '/' , ['Depth' => '0'], $xml);

        $server = $this->getServer();

        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();

        $this->assertEquals(207, $response->getStatusCode(), $responseBody);
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            'Vary'            => ['Brief,Prefer'],
        ], $response->getHeaders());


        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response'                                      => 0,
            '/d:multistatus/d:response/d:href'                               => 0,
            '/d:multistatus/d:response/d:propstat'                           => 0,
            '/d:multistatus/d:response/d:propstat/d:prop'                    => 0,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname'      => 0,
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength' => 0,
            '/d:multistatus/d:response/d:propstat/d:status'                  => 0,
        ];

        $xml = simplexml_load_string($responseBody);
        $xml->registerXPathNamespace('d', 'DAV:');
        foreach ($check as $v1 => $v2) {

            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) $count = $v2;

            $this->assertEquals($count, count($result), 'we expected ' . $count . ' appearances of ' . $xpath . ' . We found ' . count($result) . '. Full response body: ' . $responseBody);

        }

    }
    function testOR() {

        $xml = '<?xml version="1.0"?>
<d:principal-property-search xmlns:d="DAV:" test="anyof">
  <d:apply-to-principal-collection-set />
  <d:property-search>
     <d:prop>
       <d:displayname />
     </d:prop>
     <d:match>user</d:match>
  </d:property-search>
  <d:property-search>
     <d:prop>
       <d:foo />
     </d:prop>
     <d:match>bar</d:match>
  </d:property-search>
  <d:prop>
    <d:displayname />
    <d:getcontentlength />
  </d:prop>
</d:principal-property-search>';

        $request = new ServerRequest('REPORT', '/', ['Depth' => '0'], $xml);

        $server = $this->getServer();
        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();

        $this->assertEquals(207, $response->getStatusCode(), $responseBody);
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            'Vary'            => ['Brief,Prefer'],
        ], $response->getHeaders());


        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response'                                      => 2,
            '/d:multistatus/d:response/d:href'                               => 2,
            '/d:multistatus/d:response/d:propstat'                           => 4,
            '/d:multistatus/d:response/d:propstat/d:prop'                    => 4,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname'      => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength' => 2,
            '/d:multistatus/d:response/d:propstat/d:status'                  => 4,
        ];

        $xml = simplexml_load_string($responseBody);
        $xml->registerXPathNamespace('d', 'DAV:');
        foreach ($check as $v1 => $v2) {

            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) $count = $v2;

            $this->assertEquals($count, count($result), 'we expected ' . $count . ' appearances of ' . $xpath . ' . We found ' . count($result) . '. Full response body: ' . $responseBody);

        }

    }
    function testWrongUri() {

        $xml = '<?xml version="1.0"?>
<d:principal-property-search xmlns:d="DAV:">
  <d:property-search>
     <d:prop>
       <d:displayname />
     </d:prop>
     <d:match>user</d:match>
  </d:property-search>
  <d:prop>
    <d:displayname />
    <d:getcontentlength />
  </d:prop>
</d:principal-property-search>';

        $request = new ServerRequest('REPORT', '/', ['Depth' => '0'], $xml);

        $server = $this->getServer();
        

        
        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();


        $this->assertEquals(207, $response->getStatusCode(), $responseBody);
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            'Vary'            => ['Brief,Prefer'],
        ], $response->getHeaders());


        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response' => 0,
        ];

        $xml = simplexml_load_string($responseBody);
        $xml->registerXPathNamespace('d', 'DAV:');
        foreach ($check as $v1 => $v2) {

            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) $count = $v2;

            $this->assertEquals($count, count($result), 'we expected ' . $count . ' appearances of ' . $xpath . ' . We found ' . count($result) . '. Full response body: ' . $responseBody);

        }

    }
}

class MockPlugin extends Plugin {

    function getCurrentUserPrivilegeSet($node) {

        return [
            '{DAV:}read',
            '{DAV:}write',
        ];

    }

}
