<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;

class TemporaryFileFilterTest extends AbstractServer {

    function setUp() {

        parent::setUp();
        $plugin = new TemporaryFileFilterPlugin(SABRE_TEMPDIR . '/tff');
        $this->server->addPlugin($plugin);

    }

    function testPutNormal() {

        $request = new ServerRequest('PUT', '/testput.txt', [], 'Testing new file');
        $response = $this->server->handle($request);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertEquals('0', $response->getHeaderLine('Content-Length'));

        $this->assertEquals('Testing new file', file_get_contents(SABRE_TEMPDIR . '/testput.txt'));

    }

    function testPutTemp() {

        // mimicking an OS/X resource fork
        $request = new ServerRequest('PUT', '/._testput.txt', [], 'Testing new file');

        $response = $this->server->handle($request);
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $response->getHeaders());

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/._testput.txt'), '._testput.txt should not exist in the regular file structure.');

    }

    function testPutTempIfNoneMatch() {

        // mimicking an OS/X resource fork
        $request = new ServerRequest('PUT', '/._testput.txt', ['If-None-Match' => '*'], 'Testing new file');

        $response = $this->server->handle($request);
        $this->assertEquals(201, $response->getStatusCode());
        $responseBody = $response->getBody()->getContents();

        $this->assertEquals('', $responseBody);

        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $response->getHeaders());

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/._testput.txt'), '._testput.txt should not exist in the regular file structure.');

        $response = $this->server->handle($request);
        $this->assertEquals(412, $response->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

    }

    function testPutGet() {

        // mimicking an OS/X resource fork
        $request = new ServerRequest('PUT', '/._testput.txt', [], 'Testing new file');
        $response = $this->server->handle($request);


        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $response->getHeaders());

        $request = new ServerRequest('GET', '/._testput.txt');




        $response = $this->server->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp'   => ['true'],
            'Content-Length' => [16],
            'Content-Type'   => ['application/octet-stream'],
        ], $response->getHeaders());

        $this->assertEquals('Testing new file', $response->getBody()->getContents());

    }

    function testLockNonExistant() {

        mkdir(SABRE_TEMPDIR . '/locksdir');
        $locksBackend = new Locks\Backend\File(SABRE_TEMPDIR . '/locks');
        $locksPlugin = new Locks\Plugin($locksBackend);
        $this->server->addPlugin($locksPlugin);

        // mimicking an OS/X resource fork
        $request = new ServerRequest('LOCK', '/._testput.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);


        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');
        $this->assertEquals('true', $response->getHeaderLine('X-Sabre-Temp'));

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/._testlock.txt'), '._testlock.txt should not exist in the regular file structure.');

    }

    function testPutDelete() {

        // mimicking an OS/X resource fork
        $request = new ServerRequest('PUT', '/._testput.txt', [], 'Testing new file');


        $response = $this->server->handle($request);

        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $response->getHeaders());

        $request = new ServerRequest('DELETE', '/._testput.txt');

        $response = $this->server->handle($request);

        $this->assertEquals(204, $response->getStatusCode(), "Incorrect status code received. Full body:\n" . $response->getBody()->getContents());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $response->getHeaders());

        $this->assertEquals('', $response->getBody()->getContents());

    }

    function testPutPropfind() {

        // mimicking an OS/X resource fork
        $request = new ServerRequest('PUT', '/._testput.txt', [], 'Testing new file');


        $response = $this->server->handle($request);
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $response->getHeaders());

        $request = new ServerRequest('PROPFIND', '/._testput.txt');

        $response = $this->server->handle($request);


        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(207, $response->getStatusCode(), 'Incorrect status code returned. Body: ' . $responseBody);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $responseBody);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        $this->assertEquals('/._testput.txt', (string)$data, 'href element should have been /._testput.txt');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype');
        $this->assertEquals(1, count($data));

    }

}
