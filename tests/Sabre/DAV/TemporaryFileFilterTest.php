<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

class TemporaryFileFilterTest extends AbstractServer
{
    public function setup(): void
    {
        parent::setUp();
        $plugin = new TemporaryFileFilterPlugin(\Sabre\TestUtil::SABRE_TEMPDIR.'/tff');
        $this->server->addPlugin($plugin);
    }

    public function testPutNormal()
    {
        $request = new HTTP\Request('PUT', '/testput.txt', [], 'Testing new file');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        self::assertEquals('', $this->response->getBodyAsString());
        self::assertEquals(201, $this->response->status);
        self::assertEquals('0', $this->response->getHeader('Content-Length'));

        self::assertEquals('Testing new file', file_get_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/testput.txt'));
    }

    public function testPutTemp()
    {
        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        self::assertEquals('', $this->response->getBodyAsString());
        self::assertEquals(201, $this->response->status);
        self::assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        self::assertFalse(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/._testput.txt'), '._testput.txt should not exist in the regular file structure.');
    }

    public function testPutTempIfNoneMatch()
    {
        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', ['If-None-Match' => '*'], 'Testing new file');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        self::assertEquals('', $this->response->getBodyAsString());
        self::assertEquals(201, $this->response->status);
        self::assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        self::assertFalse(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/._testput.txt'), '._testput.txt should not exist in the regular file structure.');

        $this->server->exec();

        self::assertEquals(412, $this->response->status);
        self::assertEquals([
            'X-Sabre-Temp' => ['true'],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());
    }

    public function testPutGet()
    {
        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        self::assertEquals('', $this->response->getBodyAsString());
        self::assertEquals(201, $this->response->status);
        self::assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        $request = new HTTP\Request('GET', '/._testput.txt');

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(200, $this->response->status);
        self::assertEquals([
            'X-Sabre-Temp' => ['true'],
            'Content-Length' => [16],
            'Content-Type' => ['application/octet-stream'],
        ], $this->response->getHeaders());

        self::assertEquals('Testing new file', stream_get_contents($this->response->body));
    }

    public function testGetWithBrowserPlugin()
    {
        $this->server->addPlugin(new Browser\Plugin());
        $request = new HTTP\Request('GET', '/');

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(200, $this->response->status);
    }

    public function testLockNonExistant()
    {
        mkdir(\Sabre\TestUtil::SABRE_TEMPDIR.'/locksdir');
        $locksBackend = new Locks\Backend\File(\Sabre\TestUtil::SABRE_TEMPDIR.'/locks');
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
        $this->server->exec();

        self::assertEquals(201, $this->response->status);
        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');
        self::assertEquals('true', $this->response->getHeader('X-Sabre-Temp'));

        self::assertFalse(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/._testlock.txt'), '._testlock.txt should not exist in the regular file structure.');
    }

    public function testPutDelete()
    {
        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals('', $this->response->getBodyAsString());
        self::assertEquals(201, $this->response->status);
        self::assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        $request = new HTTP\Request('DELETE', '/._testput.txt');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(204, $this->response->status, "Incorrect status code received. Full body:\n".$this->response->getBodyAsString());
        self::assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        self::assertEquals('', $this->response->getBodyAsString());
    }

    public function testPutPropfind()
    {
        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $bodyAsString = $this->response->getBodyAsString();
        self::assertEquals('', $bodyAsString);
        self::assertEquals(201, $this->response->status);
        self::assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        $request = new HTTP\Request('PROPFIND', '/._testput.txt');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $bodyAsString = $this->response->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'Incorrect status code returned. Body: '.$bodyAsString);
        self::assertEquals([
            'X-Sabre-Temp' => ['true'],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", 'xmlns\\1="urn:DAV"', $bodyAsString);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        self::assertEquals('/._testput.txt', (string) $data, 'href element should have been /._testput.txt');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype');
        self::assertEquals(1, count($data));
    }
}
