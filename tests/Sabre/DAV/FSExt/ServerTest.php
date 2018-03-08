<?php declare (strict_types=1);

namespace Sabre\DAV\FSExt;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class ServerTest extends DAV\AbstractServer{

    protected function getRootNode() {

        return new Directory($this->tempDir);

    }

    function testGet() {

        $request = new HTTP\Request('GET', '/test.txt');
        $filename = $this->tempDir . '/test.txt';
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(200, $this->getResponse()->getStatusCode(), 'Invalid status code received.');
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\toDate(new \DateTime('@' . filemtime($filename)))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->getResponse()->getHeaders()
         );


        $this->assertEquals('Test contents', $this->getResponse()->getBody()->getContents());

    }

    function testHEAD() {

        $request = new HTTP\Request('HEAD', '/test.txt');
        $filename = $this->tempDir . '/test.txt';
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\toDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->getResponse()->getHeaders()
         );

        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());

    }

    function testPut() {

        $request = new HTTP\Request('PUT', '/testput.txt');
        $filename = $this->tempDir . '/testput.txt';
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
        ], $this->getResponse()->getHeaders());

        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertEquals('Testing new file', file_get_contents($filename));

    }

    function testPutAlreadyExists() {

        $request = new HTTP\Request('PUT', '/test.txt', ['If-None-Match' => '*']);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->getResponse()->getHeaders());

        $this->assertEquals(412, $this->getResponse()->getStatusCode());
        $this->assertNotEquals('Testing new file', file_get_contents($this->tempDir . '/test.txt'));

    }

    function testMkcol() {

        $request = new HTTP\Request('MKCOL', '/testcol');
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
        ], $this->getResponse()->getHeaders());

        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertTrue(is_dir($this->tempDir . '/testcol'));

    }

    function testPutUpdate() {

        $request = new HTTP\Request('PUT', '/test.txt');
        $request->setBody('Testing updated file');
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals('0', $this->getResponse()->getHeaderLine('Content-Length'));

        $this->assertEquals(204, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertEquals('Testing updated file', file_get_contents($this->tempDir . '/test.txt'));

    }

    function testDelete() {

        $request = new HTTP\Request('DELETE', '/test.txt');
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
        ], $this->getResponse()->getHeaders());

        $this->assertEquals(204, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertFalse(file_exists($this->tempDir . '/test.txt'));

    }

    function testDeleteDirectory() {

        mkdir($this->tempDir . '/testcol');
        file_put_contents($this->tempDir . '/testcol/test.txt', 'Hi! I\'m a file with a short lifespan');

        $request = new HTTP\Request('DELETE', '/testcol');
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
        ], $this->getResponse()->getHeaders());
        $this->assertEquals(204, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertFalse(file_exists($this->tempDir . '/testcol'));

    }

    function testOptions() {

        $request = new HTTP\Request('OPTIONS', '/');
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals([
            'DAV'             => ['1, 3, extended-mkcol'],
            'MS-Author-Via'   => ['DAV'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
            'Accept-Ranges'   => ['bytes'],
            'Content-Length'  => ['0'],
            'X-Sabre-Version' => [DAV\Version::VERSION],
        ], $this->getResponse()->getHeaders());

        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());

    }

    function testMove() {

        mkdir($this->tempDir . '/testcol');

        $request = new HTTP\Request('MOVE', '/test.txt', ['Destination' => '/testcol/test2.txt']);
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());

        $this->assertEquals([
            'Content-Length'  => ['0'],
            'X-Sabre-Version' => [DAV\Version::VERSION],
        ], $this->getResponse()->getHeaders());

        $this->assertTrue(
            is_file($this->tempDir . '/testcol/test2.txt')
        );


    }

    /**
     * This test checks if it's possible to move a non-FSExt collection into a
     * FSExt collection.
     *
     * The moveInto function *should* ignore the object and let sabredav itself
     * execute the slow move.
     */
    function testMoveOtherObject() {

        mkdir($this->tempDir . '/tree1');
        mkdir($this->tempDir . '/tree2');

        $tree = new DAV\Tree(new DAV\SimpleCollection('root', [
            new DAV\FS\Directory($this->tempDir . '/tree1'),
            new DAV\FSExt\Directory($this->tempDir . '/tree2'),
        ]));
        $this->server->tree = $tree;

        $request = new HTTP\Request('MOVE', '/tree1', ['Destination' => '/tree2/tree1']);
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals(201, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());

        $this->assertEquals([
            'Content-Length'  => ['0'],
            'X-Sabre-Version' => [DAV\Version::VERSION],
        ], $this->getResponse()->getHeaders());

        $this->assertTrue(
            is_dir($this->tempDir . '/tree2/tree1')
        );

    }
}
