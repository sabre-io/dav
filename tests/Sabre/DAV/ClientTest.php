<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP\Response;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    public function setup(): void
    {
        if (!function_exists('curl_init')) {
            $this->markTestSkipped('CURL must be installed to test the client');
        }
    }

    public function testConstruct()
    {
        $client = new ClientMock([
            'baseUri' => '/',
        ]);
        self::assertInstanceOf('Sabre\DAV\ClientMock', $client);
    }

    public function testConstructNoBaseUri()
    {
        $this->expectException('InvalidArgumentException');
        $client = new ClientMock([]);
    }

    public function testAuth()
    {
        $client = new ClientMock([
            'baseUri' => '/',
            'userName' => 'foo',
            'password' => 'bar',
        ]);

        self::assertEquals('foo:bar', $client->curlSettings[CURLOPT_USERPWD]);
        self::assertEquals(CURLAUTH_BASIC | CURLAUTH_DIGEST, $client->curlSettings[CURLOPT_HTTPAUTH]);
    }

    public function testBasicAuth()
    {
        $client = new ClientMock([
            'baseUri' => '/',
            'userName' => 'foo',
            'password' => 'bar',
            'authType' => Client::AUTH_BASIC,
        ]);

        self::assertEquals('foo:bar', $client->curlSettings[CURLOPT_USERPWD]);
        self::assertEquals(CURLAUTH_BASIC, $client->curlSettings[CURLOPT_HTTPAUTH]);
    }

    public function testDigestAuth()
    {
        $client = new ClientMock([
            'baseUri' => '/',
            'userName' => 'foo',
            'password' => 'bar',
            'authType' => Client::AUTH_DIGEST,
        ]);

        self::assertEquals('foo:bar', $client->curlSettings[CURLOPT_USERPWD]);
        self::assertEquals(CURLAUTH_DIGEST, $client->curlSettings[CURLOPT_HTTPAUTH]);
    }

    public function testNTLMAuth()
    {
        $client = new ClientMock([
            'baseUri' => '/',
            'userName' => 'foo',
            'password' => 'bar',
            'authType' => Client::AUTH_NTLM,
        ]);

        self::assertEquals('foo:bar', $client->curlSettings[CURLOPT_USERPWD]);
        self::assertEquals(CURLAUTH_NTLM, $client->curlSettings[CURLOPT_HTTPAUTH]);
    }

    public function testProxy()
    {
        $client = new ClientMock([
            'baseUri' => '/',
            'proxy' => 'localhost:8888',
        ]);

        self::assertEquals('localhost:8888', $client->curlSettings[CURLOPT_PROXY]);
    }

    public function testEncoding()
    {
        $client = new ClientMock([
            'baseUri' => '/',
            'encoding' => Client::ENCODING_IDENTITY | Client::ENCODING_GZIP | Client::ENCODING_DEFLATE,
        ]);

        self::assertEquals('identity,deflate,gzip', $client->curlSettings[CURLOPT_ENCODING]);
    }

    public function testPropFind()
    {
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<multistatus xmlns="DAV:">
  <response>
    <href>/foo</href>
    <propstat>
      <prop>
        <displayname>bar</displayname>
      </prop>
      <status>HTTP/1.1 200 OK</status>
    </propstat>
  </response>
</multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $result = $client->propFind('foo', ['{DAV:}displayname', '{urn:zim}gir']);

        self::assertEquals(['{DAV:}displayname' => 'bar'], $result);

        $request = $client->request;
        self::assertEquals('PROPFIND', $request->getMethod());
        self::assertEquals('/foo', $request->getUrl());
        self::assertEquals([
            'Depth' => ['0'],
            'Content-Type' => ['application/xml'],
        ], $request->getHeaders());
    }

    public function testPropFindError()
    {
        $this->expectException('Sabre\HTTP\ClientHttpException');
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $client->response = new Response(405, []);
        $client->propFind('foo', ['{DAV:}displayname', '{urn:zim}gir']);
    }

    public function testPropFindDepth1()
    {
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<multistatus xmlns="DAV:">
  <response>
    <href>/foo</href>
    <propstat>
      <prop>
        <displayname>bar</displayname>
      </prop>
      <status>HTTP/1.1 200 OK</status>
    </propstat>
  </response>
</multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $result = $client->propFind('foo', ['{DAV:}displayname', '{urn:zim}gir'], 1);

        self::assertEquals([
            '/foo' => [
            '{DAV:}displayname' => 'bar',
            ],
        ], $result);

        $request = $client->request;
        self::assertEquals('PROPFIND', $request->getMethod());
        self::assertEquals('/foo', $request->getUrl());
        self::assertEquals([
            'Depth' => ['1'],
            'Content-Type' => ['application/xml'],
        ], $request->getHeaders());
    }

    public function testPropPatch()
    {
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<multistatus xmlns="DAV:">
  <response>
    <href>/foo</href>
    <propstat>
      <prop>
        <displayname>bar</displayname>
      </prop>
      <status>HTTP/1.1 200 OK</status>
    </propstat>
  </response>
</multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $result = $client->propPatch('foo', ['{DAV:}displayname' => 'hi', '{urn:zim}gir' => null]);
        self::assertTrue($result);
        $request = $client->request;
        self::assertEquals('PROPPATCH', $request->getMethod());
        self::assertEquals('/foo', $request->getUrl());
        self::assertEquals([
            'Content-Type' => ['application/xml'],
        ], $request->getHeaders());
    }

    /**
     * @depends testPropPatch
     */
    public function testPropPatchHTTPError()
    {
        $this->expectException('Sabre\HTTP\ClientHttpException');
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $client->response = new Response(403, [], '');
        $client->propPatch('foo', ['{DAV:}displayname' => 'hi', '{urn:zim}gir' => null]);
    }

    /**
     * @depends testPropPatch
     */
    public function testPropPatchMultiStatusError()
    {
        $this->expectException('Sabre\HTTP\ClientException');
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<multistatus xmlns="DAV:">
<response>
  <href>/foo</href>
  <propstat>
    <prop>
      <displayname />
    </prop>
    <status>HTTP/1.1 403 Forbidden</status>
  </propstat>
</response>
</multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $client->propPatch('foo', ['{DAV:}displayname' => 'hi', '{urn:zim}gir' => null]);
    }

    public function testOPTIONS()
    {
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $client->response = new Response(207, [
            'DAV' => 'calendar-access, extended-mkcol',
        ]);
        $result = $client->options();

        self::assertEquals(
            ['calendar-access', 'extended-mkcol'],
            $result
        );

        $request = $client->request;
        self::assertEquals('OPTIONS', $request->getMethod());
        self::assertEquals('/', $request->getUrl());
        self::assertEquals([
        ], $request->getHeaders());
    }
}
