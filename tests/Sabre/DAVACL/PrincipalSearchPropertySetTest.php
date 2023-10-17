<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class PrincipalSearchPropertySetTest extends \PHPUnit\Framework\TestCase
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
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        self::assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        self::assertEquals($plugin, $fakeServer->getPlugin('acl'));

        return $fakeServer;
    }

    public function testDepth1()
    {
        $xml = '<?xml version="1.0"?>
<d:principal-search-property-set xmlns:d="DAV:" />';

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

        self::assertEquals(400, $server->httpResponse->status);
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $server->httpResponse->getHeaders());
    }

    public function testDepthIncorrectXML()
    {
        $xml = '<?xml version="1.0"?>
<d:principal-search-property-set xmlns:d="DAV:"><d:ohell /></d:principal-search-property-set>';

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

        self::assertEquals(400, $server->httpResponse->status, $server->httpResponse->getBodyAsString());
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $server->httpResponse->getHeaders());
    }

    public function testCorrect()
    {
        $xml = '<?xml version="1.0"?>
<d:principal-search-property-set xmlns:d="DAV:"/>';

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

        $bodyAsString = $server->httpResponse->getBodyAsString();
        self::assertEquals(200, $server->httpResponse->status, $bodyAsString);
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $server->httpResponse->getHeaders());

        $check = [
            '/d:principal-search-property-set',
            '/d:principal-search-property-set/d:principal-search-property' => 2,
            '/d:principal-search-property-set/d:principal-search-property/d:prop' => 2,
            '/d:principal-search-property-set/d:principal-search-property/d:prop/d:displayname' => 1,
            '/d:principal-search-property-set/d:principal-search-property/d:prop/s:email-address' => 1,
            '/d:principal-search-property-set/d:principal-search-property/d:description' => 2,
        ];

        $xml = simplexml_load_string($bodyAsString);
        $xml->registerXPathNamespace('d', 'DAV:');
        $xml->registerXPathNamespace('s', 'http://sabredav.org/ns');
        foreach ($check as $v1 => $v2) {
            $xpath = is_int($v1) ? $v2 : $v1;

            $result = $xml->xpath($xpath);

            $count = 1;
            if (!is_int($v1)) {
                $count = $v2;
            }

            self::assertEquals($count, count($result), 'we expected '.$count.' appearances of '.$xpath.' . We found '.count($result).'. Full response body: '.$bodyAsString);
        }
    }
}
