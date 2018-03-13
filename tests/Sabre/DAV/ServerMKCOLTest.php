<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;

class ServerMKCOLTest extends AbstractServer {

    function testMkcol() {
        $request = new ServerRequest('MKCOL', '/testcol', [], '');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Length'  => ['0'],
        ], $response->getHeaders());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertTrue(is_dir($this->tempDir . '/testcol'));

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLUnknownBody() {

        $request = new ServerRequest('MKCOL', '/testcol', [], 'Hello');
        $response = $this->server->handle($request);
        
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(415, $response->getStatusCode());

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLBrokenXML() {

        $request = new ServerRequest('MKCOL', '/testcol', ['Content-Type' => 'application/xml'], 'Hello');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(400, $response->getStatusCode(), $response->getBody()->getContents());

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLUnknownXML() {
        $request = new ServerRequest('MKCOL', '/testcol', ['Content-Type' => 'application/xml'],
            '<?xml version="1.0"?><html></html>');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(400, $response->getStatusCode());

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLNoResourceType() {
        $request = new ServerRequest('MKCOL', '/testcol', ['Content-Type' => 'application/xml'],'<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <displayname>Evert</displayname>
    </prop>
  </set>
</mkcol>');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(400, $response->getStatusCode(), 'Wrong statuscode received. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLIncorrectResourceType() {
        $request = new ServerRequest('MKCOL', '/testcol', ['Content-Type' => 'application/xml'],
            '<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /><blabla /></resourcetype>
    </prop>
  </set>
</mkcol>');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(403, $response->getStatusCode(), 'Wrong statuscode received. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLSuccess() {
        $request = new ServerRequest('MKCOL', '/testcol', ['Content-Type' => 'application/xml'],'<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /></resourcetype>
    </prop>
  </set>
</mkcol>');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Length'  => ['0'],
        ], $response->getHeaders());

        $this->assertEquals(201, $response->getStatusCode(), 'Wrong statuscode received. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLWhiteSpaceResourceType() {
        $request = new ServerRequest('MKCOL', '/testcol', ['Content-Type' => 'application/xml'],'<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype>
            <collection />
        </resourcetype>
    </prop>
  </set>
</mkcol>');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Length'  => ['0'],
        ], $response->getHeaders());

        $this->assertEquals(201, $response->getStatusCode(), 'Wrong statuscode received. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLNoParent() {

        $request = new ServerRequest('MKCOL', '/testnoparent/409me', ['Content-Type' => 'application/xml'],'');

        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(409, $response->getStatusCode(), 'Wrong statuscode received. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLParentIsNoCollection() {
        $request = new ServerRequest('MKCOL', '/test.txt/409me', ['Content-Type' => 'application/xml'], '');

        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(409, $response->getStatusCode(), 'Wrong statuscode received. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLAlreadyExists() {

        $request = new ServerRequest('MKCOL', '/test.txt', [], '');

        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
        ], $response->getHeaders());

        $this->assertEquals(405, $response->getStatusCode(), 'Wrong statuscode received. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testMKCOLSuccess
     * @depends testMKCOLAlreadyExists
     */
    function testMKCOLAndProps() {

        $request = new ServerRequest(
            'MKCOL',
            '/testcol',
            ['Content-Type' => 'application/xml'],
            '<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /></resourcetype>
        <displayname>my new collection</displayname>
    </prop>
  </set>
</mkcol>');
        $response = $this->server->handle($request);

        $responseBody = $response->getBody()->getContents();

        $this->assertEquals(207, $response->getStatusCode(), 'Wrong statuscode received. Full response body: ' . $responseBody);

        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());



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
            $responseBody
        );

    }

}
