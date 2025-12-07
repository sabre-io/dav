<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class PrincipalPropertySearchTest extends \PHPUnit\Framework\TestCase
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
        $plugin = new MockPlugin();
        $plugin->allowAccessToNodesWithoutACL = true;
        $plugin->allowUnauthenticatedAccess = false;

        self::assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        self::assertEquals($plugin, $fakeServer->getPlugin('acl'));

        return $fakeServer;
    }

    public function testDepth1()
    {
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

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_DEPTH' => '1',
            'REQUEST_URI' => '/principals',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;

        $server->exec();

        self::assertEquals(400, $server->httpResponse->getStatus(), $server->httpResponse->getBodyAsString());
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $server->httpResponse->getHeaders());
    }

    public function testUnknownSearchField()
    {
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

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_DEPTH' => '0',
            'REQUEST_URI' => '/principals',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;

        $server->exec();

        self::assertEquals(207, $server->httpResponse->getStatus(), 'Full body: '.$server->httpResponse->getBodyAsString());
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Vary' => ['Brief,Prefer'],
        ], $server->httpResponse->getHeaders());
    }

    public function testCorrect()
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

        $server->exec();

        $bodyAsString = $server->httpResponse->getBodyAsString();
        self::assertEquals(207, $server->httpResponse->status, $bodyAsString);
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Vary' => ['Brief,Prefer'],
        ], $server->httpResponse->getHeaders());

        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response' => 2,
            '/d:multistatus/d:response/d:href' => 2,
            '/d:multistatus/d:response/d:propstat' => 4,
            '/d:multistatus/d:response/d:propstat/d:prop' => 4,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname' => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength' => 2,
            '/d:multistatus/d:response/d:propstat/d:status' => 4,
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

            self::assertEquals($count, count($result), 'we expected '.$count.' appearances of '.$xpath.' . We found '.count($result).'. Full response body: '.$server->httpResponse->getBodyAsString());
        }
    }

    public function testAND()
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

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_DEPTH' => '0',
            'REQUEST_URI' => '/',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;

        $server->exec();

        $bodyAsString = $server->httpResponse->getBodyAsString();
        self::assertEquals(207, $server->httpResponse->status, $bodyAsString);
        self::assertEquals([
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

            self::assertEquals($count, count($result), 'we expected '.$count.' appearances of '.$xpath.' . We found '.count($result).'. Full response body: '.$server->httpResponse->getBodyAsString());
        }
    }

    public function testOR()
    {
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

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_DEPTH' => '0',
            'REQUEST_URI' => '/',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;

        $server->exec();

        $bodyAsString = $server->httpResponse->getBodyAsString();
        self::assertEquals(207, $server->httpResponse->status, $bodyAsString);
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Vary' => ['Brief,Prefer'],
        ], $server->httpResponse->getHeaders());

        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response' => 2,
            '/d:multistatus/d:response/d:href' => 2,
            '/d:multistatus/d:response/d:propstat' => 4,
            '/d:multistatus/d:response/d:propstat/d:prop' => 4,
            '/d:multistatus/d:response/d:propstat/d:prop/d:displayname' => 2,
            '/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength' => 2,
            '/d:multistatus/d:response/d:propstat/d:status' => 4,
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

            self::assertEquals($count, count($result), 'we expected '.$count.' appearances of '.$xpath.' . We found '.count($result).'. Full response body: '.$server->httpResponse->getBodyAsString());
        }
    }

    public function testWrongUri()
    {
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

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_DEPTH' => '0',
            'REQUEST_URI' => '/',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;

        $server->exec();

        $bodyAsString = $server->httpResponse->getBodyAsString();
        self::assertEquals(207, $server->httpResponse->status, $bodyAsString);
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Vary' => ['Brief,Prefer'],
        ], $server->httpResponse->getHeaders());

        $check = [
            '/d:multistatus',
            '/d:multistatus/d:response' => 0,
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

            self::assertEquals($count, count($result), 'we expected '.$count.' appearances of '.$xpath.' . We found '.count($result).'. Full response body: '.$server->httpResponse->getBodyAsString());
        }
    }

    public function testEmptyRequestProperties()
    {
        $xml = '<?xml version="1.0"?>
<d:principal-property-search xmlns:d="DAV:">
  <d:property-search>
     <d:prop>
       <d:displayname />
     </d:prop>
     <d:match>1</d:match>
  </d:property-search>
</d:principal-property-search>';

        $serverVars = [
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_DEPTH' => '0',
            'REQUEST_URI' => '/principals',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($xml);

        $server = $this->getServer();
        $server->httpRequest = $request;

        $server->exec();

        self::assertEquals(207, $server->httpResponse->getStatus());
        self::assertStringContainsString('/principals/user1/', $server->httpResponse->getBodyAsString());
    }
}

class MockPlugin extends Plugin
{
    public function getCurrentUserPrivilegeSet($node)
    {
        return [
            '{DAV:}read',
            '{DAV:}write',
        ];
    }
}
