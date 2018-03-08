<?php declare (strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

class TemporaryFileFilterTest extends AbstractServer {

    function setUp() {

        parent::setUp();
        $plugin = new TemporaryFileFilterPlugin(SABRE_TEMPDIR . '/tff');
        $this->server->addPlugin($plugin);

    }

    function testPutNormal() {

        $request = new HTTP\Request('PUT', '/testput.txt', [], 'Testing new file');

        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals('0', $this->getResponse()->getHeaderLine('Content-Length'));

        $this->assertEquals('Testing new file', file_get_contents(SABRE_TEMPDIR . '/testput.txt'));

    }

    function testPutTemp() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');

        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->getResponse()->getHeaders());

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/._testput.txt'), '._testput.txt should not exist in the regular file structure.');

    }

    function testPutTempIfNoneMatch() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', ['If-None-Match' => '*'], 'Testing new file');

        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->getResponse()->getHeaders());

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/._testput.txt'), '._testput.txt should not exist in the regular file structure.');


        $this->server->start();

        $this->assertEquals(412, $this->getResponse()->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->getResponse()->getHeaders());

    }

    function testPutGet() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->getResponse()->getHeaders());

        $request = new HTTP\Request('GET', '/._testput.txt');

        $this->server->httpRequest = $request;
        $this->server->start();



        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp'   => ['true'],
            'Content-Length' => [16],
            'Content-Type'   => ['application/octet-stream'],
        ], $this->getResponse()->getHeaders());

        $this->assertEquals('Testing new file', $this->getResponse()->getBody()->getContents());

    }

    function testLockNonExistant() {

        mkdir(SABRE_TEMPDIR . '/locksdir');
        $locksBackend = new Locks\Backend\File(SABRE_TEMPDIR . '/locks');
        $locksPlugin = new Locks\Plugin($locksBackend);
        $this->server->addPlugin($locksPlugin);

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('LOCK', '/._testput.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');
        $this->assertEquals('true', $this->getResponse()->getHeaderLine('X-Sabre-Temp'));

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/._testlock.txt'), '._testlock.txt should not exist in the regular file structure.');

    }

    function testPutDelete() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');

        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->getResponse()->getHeaders());

        $request = new HTTP\Request('DELETE', '/._testput.txt');
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(204, $this->getResponse()->getStatusCode(), "Incorrect status code received. Full body:\n" . $this->getResponse()->getBody()->getContents());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->getResponse()->getHeaders());

        $this->assertEquals('', $this->getResponse()->getBody()->getContents());

    }

    function testPutPropfind() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->getResponse()->getHeaders());

        $request = new HTTP\Request('PROPFIND', '/._testput.txt');

        $this->server->httpRequest = ($request);
        $this->server->start();

        $responseBody = $this->getResponse()->getBody()->getContents();
        $this->assertEquals(207, $this->getResponse()->getStatusCode(), 'Incorrect status code returned. Body: ' . $responseBody);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->getResponse()->getHeaders());

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $responseBody);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        $this->assertEquals('/._testput.txt', (string)$data, 'href element should have been /._testput.txt');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype');
        $this->assertEquals(1, count($data));

    }

}
