<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

class ServerMKCOLTest extends AbstractServer
{
    public function testMkcol()
    {
        $serverVars = [
            'REQUEST_URI' => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length' => ['0'],
        ], $this->response->getHeaders());

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('', $this->response->getBodyAsString());
        $this->assertTrue(is_dir($this->tempDir.'/testcol'));
    }

    /**
     * @depends testMkcol
     */
    public function testMKCOLUnknownBody()
    {
        $serverVars = [
            'REQUEST_URI' => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('Hello');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(415, $this->response->status);
    }

    /**
     * @depends testMkcol
     */
    public function testMKCOLBrokenXML()
    {
        $serverVars = [
            'REQUEST_URI' => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('Hello');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(400, $this->response->getStatus(), $this->response->getBodyAsString());
    }

    /**
     * @depends testMkcol
     */
    public function testMKCOLUnknownXML()
    {
        $serverVars = [
            'REQUEST_URI' => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?><html></html>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(400, $this->response->getStatus());
    }

    /**
     * @depends testMkcol
     */
    public function testMKCOLNoResourceType()
    {
        $serverVars = [
            'REQUEST_URI' => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <displayname>Evert</displayname>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(400, $this->response->status, 'Wrong statuscode received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testMkcol
     */
    public function testMKCOLIncorrectResourceType()
    {
        $serverVars = [
            'REQUEST_URI' => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /><blabla /></resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(403, $this->response->status, 'Wrong statuscode received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    public function testMKCOLSuccess()
    {
        $serverVars = [
            'REQUEST_URI' => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /></resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length' => ['0'],
        ], $this->response->getHeaders());

        $this->assertEquals(201, $this->response->status, 'Wrong statuscode received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    public function testMKCOLWhiteSpaceResourceType()
    {
        $serverVars = [
            'REQUEST_URI' => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype>
            <collection />
        </resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length' => ['0'],
        ], $this->response->getHeaders());

        $this->assertEquals(201, $this->response->status, 'Wrong statuscode received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    public function testMKCOLNoParent()
    {
        $serverVars = [
            'REQUEST_URI' => '/testnoparent/409me',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(409, $this->response->status, 'Wrong statuscode received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    public function testMKCOLParentIsNoCollection()
    {
        $serverVars = [
            'REQUEST_URI' => '/test.txt/409me',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(409, $this->response->status, 'Wrong statuscode received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    public function testMKCOLAlreadyExists()
    {
        $serverVars = [
            'REQUEST_URI' => '/test.txt',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Allow' => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
        ], $this->response->getHeaders());

        $this->assertEquals(405, $this->response->status, 'Wrong statuscode received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testMKCOLSuccess
     * @depends testMKCOLAlreadyExists
     */
    public function testMKCOLAndProps()
    {
        $request = new HTTP\Request(
            'MKCOL',
            '/testcol',
            ['Content-Type' => 'application/xml']
        );
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /></resourcetype>
        <displayname>my new collection</displayname>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $bodyAsString = $this->response->getBodyAsString();
        $this->assertEquals(207, $this->response->status, 'Wrong statuscode received. Full response body: '.$bodyAsString);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
 <d:response>
  <d:href>/testcol</d:href>
  <d:propstat>
   <d:prop>
    <d:displayname />
   </d:prop>
   <d:status>HTTP/1.1 403 Forbidden</d:status>
  </d:propstat>
 </d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $bodyAsString
        );
    }
}
