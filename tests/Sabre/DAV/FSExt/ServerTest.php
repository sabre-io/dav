<?php declare (strict_types=1);

namespace Sabre\DAV\FSExt;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class ServerTest extends DAV\AbstractServer{

    protected function getRootNode() {

        return new Directory($this->tempDir);

    }

    function testGet() {

        $request = new ServerRequest('GET', '/test.txt');
        $filename = $this->tempDir . '/test.txt';

        $response = $this->server->handle($request);


        $this->assertEquals(200, $response->getStatusCode(), 'Invalid status code received.');
        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\toDate(new \DateTime('@' . filemtime($filename)))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $response->getHeaders()
         );


        $this->assertEquals('Test contents', $response->getBody()->getContents());

    }

    function testHEAD() {

        $request = new ServerRequest('HEAD', '/test.txt');
        $filename = $this->tempDir . '/test.txt';
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\toDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $response->getHeaders()
         );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());

    }

    function testPut() {

        $request = new ServerRequest('PUT', '/testput.txt', [], 'Testing new file');
        $filename = $this->tempDir . '/testput.txt';
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Length'  => ['0'],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
        ], $response->getHeaders());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertEquals('Testing new file', file_get_contents($filename));

    }

    function testPutAlreadyExists() {

        $request = new ServerRequest('PUT', '/test.txt', ['If-None-Match' => '*'], 'Testing new file');
        $response = $this->server->handle($request);

        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(412, $response->getStatusCode());
        $this->assertNotEquals('Testing new file', file_get_contents($this->tempDir . '/test.txt'));

    }

    function testMkcol() {

        $request = new ServerRequest('MKCOL', '/testcol');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Length'  => ['0'],
        ], $response->getHeaders());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertTrue(is_dir($this->tempDir . '/testcol'));

    }

    function testPutUpdate() {

        $request = new ServerRequest('PUT', '/test.txt', [], 'Testing updated file');
        $response = $this->server->handle($request);


        $this->assertEquals('0', $response->getHeaderLine('Content-Length'));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertEquals('Testing updated file', file_get_contents($this->tempDir . '/test.txt'));

    }

    function testDelete() {

        $request = new ServerRequest('DELETE', '/test.txt');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Length'  => ['0'],
        ], $response->getHeaders());

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertFalse(file_exists($this->tempDir . '/test.txt'));

    }

    function testDeleteDirectory() {

        mkdir($this->tempDir . '/testcol');
        file_put_contents($this->tempDir . '/testcol/test.txt', 'Hi! I\'m a file with a short lifespan');

        $request = new ServerRequest('DELETE', '/testcol');
        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Length'  => ['0'],
        ], $response->getHeaders());
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertFalse(file_exists($this->tempDir . '/testcol'));

    }

    function testOptions() {

        $request = new ServerRequest('OPTIONS', '/');
        $response = $this->server->handle($request);


        $this->assertEquals([
            'DAV'             => ['1, 3, extended-mkcol'],
            'MS-Author-Via'   => ['DAV'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
            'Accept-Ranges'   => ['bytes'],
            'Content-Length'  => ['0'],

        ], $response->getHeaders());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());

    }

    function testMove() {

        mkdir($this->tempDir . '/testcol');

        $request = new ServerRequest('MOVE', '/test.txt', ['Destination' => '/testcol/test2.txt']);
        $response = $this->server->handle($request);


        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());

        $this->assertEquals([
            'Content-Length'  => ['0'],

        ], $response->getHeaders());

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

        $request = new ServerRequest('MOVE', '/tree1', ['Destination' => '/tree2/tree1']);
        $response = $this->server->handle($request);


        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());

        $this->assertEquals([
            'Content-Length'  => ['0'],

        ], $response->getHeaders());

        $this->assertTrue(
            is_dir($this->tempDir . '/tree2/tree1')
        );

    }

    function testCopy() {

        mkdir($this->tempDir . '/testcol');

        $request = new ServerRequest('COPY', '/test.txt', ['Destination' => '/testcol/test2.txt']);
        $response = $this->server->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(0, $response->getBody()->getSize());

        $this->assertEquals([
            'Content-Length'  => ['0'],

        ], $response->getHeaders());

        $this->assertTrue(is_file($this->tempDir . '/test.txt'));
        $this->assertTrue(is_file($this->tempDir . '/testcol/test2.txt'));

    }
}
