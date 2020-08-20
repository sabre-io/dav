<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class PrincipalPropertySearchBlockTest extends \PHPUnit\Framework\TestCase
{
    public function getServer()
    {
        $backend = new PrincipalBackend\Mock();

        $dir = new DAV\SimpleCollection('root');
        $principals = new PrincipalCollection($backend);
        $dir->addChild($principals);

        $fakeServer = new DAV\Server($dir);
        $fakeServer->sapi = new HTTP\SapiMock();
        $fakeServer->httpResponse = new HTTP\ResponseMock();
        $fakeServer->debugExceptions = true;
        $plugin = new MockPluginRestrictive();
        $plugin->allowAccessToNodesWithoutACL = false;
        $plugin->allowUnauthenticatedAccess = false;
        $fakeServer->addPlugin($plugin);
        $fakeServer->addPlugin(
            new DAV\Auth\Plugin(
                new DAV\Auth\Backend\Mock()
            )
        );
        // Login
        $fakeServer->getPlugin('auth')->beforeMethod(
            new \Sabre\HTTP\Request('GET', '/'),
            new \Sabre\HTTP\Response()
        );

        return $fakeServer;
    }

    public function testPartialAccess()
    {
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
            'HTTP_DEPTH' => '0',
            'REQUEST_URI' => '/',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;

        $server->start();

        $bodyAsString = $server->httpResponse->getBodyAsString();
        $this->assertEquals(207, $server->httpResponse->status, $bodyAsString);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Vary' => ['Brief,Prefer'],
        ], $server->httpResponse->getHeaders());

        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response' => 2,
            '/d:multistatus/d:response/d:href' => 2,
            '/d:multistatus/d:response/d:propstat' => 3,
            '/d:multistatus/d:response/d:propstat/d:prop' => 3,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname' => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength' => 2,
            '/d:multistatus/d:response/d:propstat/d:status' => 3,
        ];

        $xml = simplexml_load_string($bodyAsString);
        $xml->registerXPathNamespace('d', 'DAV:');
        foreach ($check as $v1 => $v2) {
            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) {
                $count = $v2;
            }

            $this->assertEquals($count, count($result), 'we expected '.$count.' appearances of '.$xpath.' . We found '.count($result).'. Full response body: '.$server->httpResponse->getBodyAsString());
        }
        $result = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals('HTTP/1.1 403 Forbidden', $result[2]);
    }

    public function testPartialAccessHidden()
    {
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
            'HTTP_DEPTH' => '0',
            'REQUEST_URI' => '/',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;
        $server->getPlugin('acl')->hideNodesFromListings = true;

        $server->start();

        $bodyAsString = $server->httpResponse->getBodyAsString();
        $this->assertEquals(207, $server->httpResponse->status, $bodyAsString);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Vary' => ['Brief,Prefer'],
        ], $server->httpResponse->getHeaders());

        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response' => 1,
            '/d:multistatus/d:response/d:href' => 1,
            '/d:multistatus/d:response/d:propstat' => 2,
            '/d:multistatus/d:response/d:propstat/d:prop' => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname' => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength' => 1,
            '/d:multistatus/d:response/d:propstat/d:status' => 2,
        ];

        $xml = simplexml_load_string($bodyAsString);
        $xml->registerXPathNamespace('d', 'DAV:');
        foreach ($check as $v1 => $v2) {
            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) {
                $count = $v2;
            }

            $this->assertEquals($count, count($result), 'we expected '.$count.' appearances of '.$xpath.' . We found '.count($result).'. Full response body: '.$server->httpResponse->getBodyAsString());
        }
    }

    public function testNoAccess()
    {
        $xml = '<?xml version="1.0"?>
<d:principal-property-search xmlns:d="DAV:">
  <d:apply-to-principal-collection-set />
  <d:property-search>
     <d:prop>
       <d:displayname />
     </d:prop>
     <d:match>user 2</d:match>
  </d:property-search>
  <d:prop>
    <d:displayname />
    <d:getcontentlength />
  </d:prop>
</d:principal-property-search>';

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_DEPTH' => '0',
            'REQUEST_URI' => '/',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;

        $server->start();

        $bodyAsString = $server->httpResponse->getBodyAsString();
        $this->assertEquals(207, $server->httpResponse->status, $bodyAsString);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Vary' => ['Brief,Prefer'],
        ], $server->httpResponse->getHeaders());

        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response' => 1,
            '/d:multistatus/d:response/d:href' => 1,
            '/d:multistatus/d:response/d:propstat' => 1,
            '/d:multistatus/d:response/d:propstat/d:prop' => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname' => 1,
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength' => 1,
            '/d:multistatus/d:response/d:propstat/d:status' => 1,
        ];

        $xml = simplexml_load_string($bodyAsString);
        $xml->registerXPathNamespace('d', 'DAV:');
        foreach ($check as $v1 => $v2) {
            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) {
                $count = $v2;
            }

            $this->assertEquals($count, count($result), 'we expected '.$count.' appearances of '.$xpath.' . We found '.count($result).'. Full response body: '.$server->httpResponse->getBodyAsString());
        }
        $result = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals('HTTP/1.1 403 Forbidden', $result[0]);
    }

    public function testNoAccessHidden()
    {
        $xml = '<?xml version="1.0"?>
<d:principal-property-search xmlns:d="DAV:">
  <d:apply-to-principal-collection-set />
  <d:property-search>
     <d:prop>
       <d:displayname />
     </d:prop>
     <d:match>user 2</d:match>
  </d:property-search>
  <d:prop>
    <d:displayname />
    <d:getcontentlength />
  </d:prop>
</d:principal-property-search>';

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_DEPTH' => '0',
            'REQUEST_URI' => '/',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;
        $server->getPlugin('acl')->hideNodesFromListings = true;

        $server->start();

        $bodyAsString = $server->httpResponse->getBodyAsString();
        $this->assertEquals(207, $server->httpResponse->status, $bodyAsString);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Vary' => ['Brief,Prefer'],
        ], $server->httpResponse->getHeaders());

        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response' => 0,
            '/d:multistatus/d:response/d:href' => 0,
            '/d:multistatus/d:response/d:propstat' => 0,
            '/d:multistatus/d:response/d:propstat/d:prop' => 0,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname' => 0,
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength' => 0,
            '/d:multistatus/d:response/d:propstat/d:status' => 0,
        ];

        $xml = simplexml_load_string($bodyAsString);
        $xml->registerXPathNamespace('d', 'DAV:');
        foreach ($check as $v1 => $v2) {
            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) {
                $count = $v2;
            }

            $this->assertEquals($count, count($result), 'we expected '.$count.' appearances of '.$xpath.' . We found '.count($result).'. Full response body: '.$server->httpResponse->getBodyAsString());
        }
    }
}

class MockPluginRestrictive extends Plugin
{
    public function getCurrentUserPrivilegeSet($node)
    {
        if ('principals/user2' === $node) {
            return [];
        } else {
            return ['{DAV:}read'];
        }
    }
}
