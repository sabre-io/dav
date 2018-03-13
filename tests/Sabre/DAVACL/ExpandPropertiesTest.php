<?php declare (strict_types=1);

namespace Sabre\DAVACL;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class ExpandPropertiesTest extends \PHPUnit_Framework_TestCase {

    function getServer() {

        $tree = [
            new DAV\Mock\PropertiesCollection('node1', [], [
                '{http://sabredav.org/ns}simple' => 'foo',
                '{http://sabredav.org/ns}href'   => new DAV\Xml\Property\Href('node2'),
                '{DAV:}displayname'              => 'Node 1',
            ]),
            new DAV\Mock\PropertiesCollection('node2', [], [
                '{http://sabredav.org/ns}simple'   => 'simple',
                '{http://sabredav.org/ns}hreflist' => new DAV\Xml\Property\Href(['node1', 'node3']),
                '{DAV:}displayname'                => 'Node 2',
            ]),
            new DAV\Mock\PropertiesCollection('node3', [], [
                '{http://sabredav.org/ns}simple' => 'simple',
                '{DAV:}displayname'              => 'Node 3',
            ]),
        ];

        $fakeServer = new DAV\Server($tree, null, null, function(){});
        $fakeServer->debugExceptions = true;
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        // Anyone can do anything
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ]
        ]);
        $this->assertTrue($plugin instanceof Plugin);

        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('acl'));

        return $fakeServer;

    }

    function testSimple() {

        $xml = '<?xml version="1.0"?>
<d:expand-property xmlns:d="DAV:">
  <d:property name="displayname" />
  <d:property name="foo" namespace="http://www.sabredav.org/NS/2010/nonexistant" />
  <d:property name="simple" namespace="http://sabredav.org/ns" />
  <d:property name="href" namespace="http://sabredav.org/ns" />
</d:expand-property>';

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'Depth'     => '0',
            '/node1',
        ];

        $request = new ServerRequest('REPORT', '/node1', [
            'Depth' => '0'
        ], $xml);

        $server = $this->getServer();

        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(207, $response->getStatusCode(), 'Incorrect status code received. Full body: ' . $responseBody);
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());


        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response'                                 => 1,
            '/d:multistatus/d:response/d:href'                          => 1,
            '/d:multistatus/d:response/d:propstat'                      => 2,
            '/d:multistatus/d:response/d:propstat/d:prop'               => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname' => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:simple'      => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:href'        => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:href/d:href' => 1,
        ];

        $xml = simplexml_load_string($responseBody);
        $xml->registerXPathNamespace('d', 'DAV:');
        $xml->registerXPathNamespace('s', 'http://sabredav.org/ns');
        foreach ($check as $v1 => $v2) {

            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) $count = $v2;

            $this->assertEquals($count, count($result), 'we expected ' . $count . ' appearances of ' . $xpath . ' . We found ' . count($result) . '. Full response: ' . $responseBody);

        }

    }

    /**
     * @depends testSimple
     */
    function testExpand() {

        $xml = '<?xml version="1.0"?>
<d:expand-property xmlns:d="DAV:">
  <d:property name="href" namespace="http://sabredav.org/ns">
      <d:property name="displayname" />
  </d:property>
</d:expand-property>';

        $request = new ServerRequest('REPORT','/node1',['Depth' => '0'], $xml);

        $server = $this->getServer();
        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(207, $response->getStatusCode(), 'Incorrect response status received. Full response body: ' . $responseBody);
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());


        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response'                                                                     => 1,
            '/d:multistatus/d:response/d:href'                                                              => 1,
            '/d:multistatus/d:response/d:propstat'                                                          => 1,
            '/d:multistatus/d:response/d:propstat/d:prop'                                                   => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:href'                                            => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:href/d:response'                                 => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:href/d:response/d:href'                          => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:href/d:response/d:propstat'                      => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:href/d:response/d:propstat/d:prop'               => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:href/d:response/d:propstat/d:prop/d:displayname' => 1,
        ];

        $xml = simplexml_load_string($responseBody);
        $xml->registerXPathNamespace('d', 'DAV:');
        $xml->registerXPathNamespace('s', 'http://sabredav.org/ns');
        foreach ($check as $v1 => $v2) {

            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) $count = $v2;

            $this->assertEquals($count, count($result), 'we expected ' . $count . ' appearances of ' . $xpath . ' . We found ' . count($result) . ' Full response body: ' . $responseBody);

        }

    }

    /**
     * @depends testSimple
     */
    function testExpandHrefList() {

        $xml = '<?xml version="1.0"?>
<d:expand-property xmlns:d="DAV:">
  <d:property name="hreflist" namespace="http://sabredav.org/ns">
      <d:property name="displayname" />
  </d:property>
</d:expand-property>';

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'Depth'     => '0',
            '/node2',
        ];

        $request = new ServerRequest('REPORT', '/node2', ['Depth' => '0'], $xml);
        $server = $this->getServer();
        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(207, $response->getStatusCode());
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());


        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response'                                                                         => 1,
            '/d:multistatus/d:response/d:href'                                                                  => 1,
            '/d:multistatus/d:response/d:propstat'                                                              => 1,
            '/d:multistatus/d:response/d:propstat/d:prop'                                                       => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist'                                            => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response'                                 => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:href'                          => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat'                      => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop'               => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop/d:displayname' => 2,
        ];

        $xml = simplexml_load_string($responseBody);
        $xml->registerXPathNamespace('d', 'DAV:');
        $xml->registerXPathNamespace('s', 'http://sabredav.org/ns');
        foreach ($check as $v1 => $v2) {

            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) $count = $v2;

            $this->assertEquals($count, count($result), 'we expected ' . $count . ' appearances of ' . $xpath . ' . We found ' . count($result));

        }

    }

    /**
     * @depends testExpand
     */
    function testExpandDeep() {

        $xml = '<?xml version="1.0"?>
<d:expand-property xmlns:d="DAV:">
  <d:property name="hreflist" namespace="http://sabredav.org/ns">
      <d:property name="href" namespace="http://sabredav.org/ns">
          <d:property name="displayname" />
      </d:property>
      <d:property name="displayname" />
  </d:property>
</d:expand-property>';

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'Depth'     => '0',
            '/node2',
        ];

        $request = new ServerRequest('REPORT', '/node2', ['Depth' => '0'], $xml);

        $server = $this->getServer();
        $response = $server->handle($request);
        $responseBody = $response->getBody()->getContents();

        $this->assertEquals(207, $response->getStatusCode());
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());


        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response'                                                                                                             => 1,
            '/d:multistatus/d:response/d:href'                                                                                                      => 1,
            '/d:multistatus/d:response/d:propstat'                                                                                                  => 1,
            '/d:multistatus/d:response/d:propstat/d:prop'                                                                                           => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist'                                                                                => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response'                                                                     => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:href'                                                              => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat'                                                          => 3,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop'                                                   => 3,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop/d:displayname'                                     => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop/s:href'                                            => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop/s:href/d:response'                                 => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop/s:href/d:response/d:href'                          => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop/s:href/d:response/d:propstat'                      => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop/s:href/d:response/d:propstat/d:prop'               => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/s:hreflist/d:response/d:propstat/d:prop/s:href/d:response/d:propstat/d:prop/d:displayname' => 1,
        ];

        $xml = simplexml_load_string($responseBody);
        $xml->registerXPathNamespace('d', 'DAV:');
        $xml->registerXPathNamespace('s', 'http://sabredav.org/ns');
        foreach ($check as $v1 => $v2) {

            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) $count = $v2;

            $this->assertEquals($count, count($result), 'we expected ' . $count . ' appearances of ' . $xpath . ' . We found ' . count($result));

        }

    }
}
