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
        self::assertInstanceOf(\Sabre\DAV\ClientMock::class, $client);
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
        $this->expectException(\Sabre\HTTP\ClientHttpException::class);
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

    /**
     * A PROPFIND on a folder containing resources will filter out the meta-data
     * for resources that have a status that is not 200.
     * For example, resources that are "403" (access is forbidden to the user)
     * or "425" (too early), the resource may have been recently uploaded and
     * still has some processing happening in the server before being made
     * available for regular access.
     */
    public function testPropFindMixedErrors()
    {
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:">
  <d:response>
    <d:href>/folder1</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype><d:collection/></d:resourcetype>
        <d:displayname>Folder1</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/folder1/file1.txt</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype/>
        <d:displayname>File1</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/folder1/file2.txt</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype/>
        <d:displayname>File2</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 403 Forbidden</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/folder1/file3.txt</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype/>
        <d:displayname>File3</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 425 Too Early</d:status>
    </d:propstat>
  </d:response>
</d:multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $result = $client->propFind('folder1', ['{DAV:}resourcetype', '{DAV:}displayname', '{urn:zim}gir'], 1);

        self::assertEquals([
            '/folder1' => [
                '{DAV:}resourcetype' => new Xml\Property\ResourceType('{DAV:}collection'),
                '{DAV:}displayname' => 'Folder1',
            ],
            '/folder1/file1.txt' => [
                '{DAV:}resourcetype' => null,
                '{DAV:}displayname' => 'File1',
            ],
            '/folder1/file2.txt' => [],
            '/folder1/file3.txt' => [],
        ], $result);

        $request = $client->request;
        self::assertEquals('PROPFIND', $request->getMethod());
        self::assertEquals('/folder1', $request->getUrl());
        self::assertEquals([
            'Depth' => ['1'],
            'Content-Type' => ['application/xml'],
        ], $request->getHeaders());
    }

    /**
     * An "unfiltered" PROPFIND on a folder containing resources will include the
     * meta-data for resources that have a status that is not 200.
     * For example, resources that are "403" (access is forbidden to the user)
     * or "425" (too early), the resource may have been recently uploaded and
     * still has some processing happening in the server before being made
     * available for regular access.
     */
    public function testPropFindUnfilteredDepth0()
    {
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:">
  <d:response>
    <d:href>/folder1</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype><d:collection/></d:resourcetype>
        <d:displayname>Folder1</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <d:contentlength></d:contentlength>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
</d:multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $result = $client->propFindUnfiltered('folder1', ['{DAV:}resourcetype', '{DAV:}displayname', '{DAV:}contentlength', '{urn:zim}gir']);

        self::assertEquals([
            200 => [
                '{DAV:}resourcetype' => new Xml\Property\ResourceType('{DAV:}collection'),
                '{DAV:}displayname' => 'Folder1',
            ],
            404 => [
                '{DAV:}contentlength' => null,
            ],
        ], $result);

        $request = $client->request;
        self::assertEquals('PROPFIND', $request->getMethod());
        self::assertEquals('/folder1', $request->getUrl());
        self::assertEquals([
            'Depth' => ['0'],
            'Content-Type' => ['application/xml'],
        ], $request->getHeaders());
    }

    /**
     * An "unfiltered" PROPFIND on a folder containing resources will include the
     * meta-data for resources that have a status that is not 200.
     * For example, resources that are "403" (access is forbidden to the user)
     * or "425" (too early), the resource may have been recently uploaded and
     * still has some processing happening in the server before being made
     * available for regular access.
     */
    public function testPropFindUnfiltered()
    {
        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:">
  <d:response>
    <d:href>/folder1</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype><d:collection/></d:resourcetype>
        <d:displayname>Folder1</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <d:contentlength></d:contentlength>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/folder1/file1.txt</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype/>
        <d:displayname>File1</d:displayname>
        <d:contentlength>12</d:contentlength>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/folder1/file2.txt</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype/>
        <d:displayname>File2</d:displayname>
        <d:contentlength>27</d:contentlength>
      </d:prop>
      <d:status>HTTP/1.1 403 Forbidden</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/folder1/file3.txt</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype/>
        <d:displayname>File3</d:displayname>
        <d:contentlength>42</d:contentlength>
      </d:prop>
      <d:status>HTTP/1.1 425 Too Early</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/folder1/subfolder</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype><d:collection/></d:resourcetype>
        <d:displayname>SubFolder</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <d:contentlength></d:contentlength>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
</d:multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $result = $client->propFindUnfiltered('folder1', ['{DAV:}resourcetype', '{DAV:}displayname', '{DAV:}contentlength', '{urn:zim}gir'], 1);

        self::assertEquals([
            '/folder1' => [
                200 => [
                    '{DAV:}resourcetype' => new Xml\Property\ResourceType('{DAV:}collection'),
                    '{DAV:}displayname' => 'Folder1',
                ],
                404 => [
                    '{DAV:}contentlength' => null,
                ],
            ],
            '/folder1/file1.txt' => [
                200 => [
                    '{DAV:}resourcetype' => null,
                    '{DAV:}displayname' => 'File1',
                    '{DAV:}contentlength' => 12,
                ],
            ],
            '/folder1/file2.txt' => [
                403 => [
                    '{DAV:}resourcetype' => null,
                    '{DAV:}displayname' => 'File2',
                    '{DAV:}contentlength' => 27,
                ],
            ],
            '/folder1/file3.txt' => [
                425 => [
                    '{DAV:}resourcetype' => null,
                    '{DAV:}displayname' => 'File3',
                    '{DAV:}contentlength' => 42,
                ],
            ],
            '/folder1/subfolder' => [
                200 => [
                    '{DAV:}resourcetype' => new Xml\Property\ResourceType('{DAV:}collection'),
                    '{DAV:}displayname' => 'SubFolder',
                ],
                404 => [
                    '{DAV:}contentlength' => null,
                ],
            ],
        ], $result);

        $request = $client->request;
        self::assertEquals('PROPFIND', $request->getMethod());
        self::assertEquals('/folder1', $request->getUrl());
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
        $this->expectException(\Sabre\HTTP\ClientHttpException::class);
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
        $this->expectException(\Sabre\HTTP\ClientException::class);
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
