<?php

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
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(200, $this->response->getStatus(), 'Invalid status code received.');
        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            'Last-Modified' => HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            'ETag' => '"'  .md5_file($this->tempDir . '/test.txt') . '"',
            ),
            $this->response->headers
         );


        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }

    function testHEAD() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'HEAD',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            'Last-Modified' => HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            'ETag' => '"' . md5_file($this->tempDir . '/test.txt') . '"',
            ),
            $this->response->headers
         );

        $this->assertEquals(200,$this->response->status);
        $this->assertEquals('', $this->response->body);

    }

    function testPut() {

        $serverVars = array(
            'REQUEST_URI'    => '/testput.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Length' => 0,
            'ETag'           => '"' . md5('Testing new file') . '"',
        ), $this->response->headers);

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertEquals('Testing new file',file_get_contents($this->tempDir . '/testput.txt'));

    }

    function testPutAlreadyExists() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF_NONE_MATCH' => '*',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/xml; charset=utf-8',
        ),$this->response->headers);

        $this->assertEquals(412, $this->response->status);
        $this->assertNotEquals('Testing new file',file_get_contents($this->tempDir . '/test.txt'));

    }

    function testMkcol() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody("");
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Length' => '0',
        ),$this->response->headers);

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertTrue(is_dir($this->tempDir . '/testcol'));

    }

    function testPutUpdate() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('Testing updated file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('0', $this->response->headers['Content-Length']);

        $this->assertEquals(204, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertEquals('Testing updated file',file_get_contents($this->tempDir . '/test.txt'));

    }

    function testDelete() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'DELETE',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Length' => '0',
        ),$this->response->headers);

        $this->assertEquals(204, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertFalse(file_exists($this->tempDir . '/test.txt'));

    }

    function testDeleteDirectory() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'DELETE',
        );

        mkdir($this->tempDir.'/testcol');
        file_put_contents($this->tempDir.'/testcol/test.txt','Hi! I\'m a file with a short lifespan');

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Length' => '0',
        ),$this->response->headers);
        $this->assertEquals(204, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertFalse(file_exists($this->tempDir . '/col'));

    }

    function testOptions() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'OPTIONS',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'DAV'            => '1, 3, extended-mkcol',
            'MS-Author-Via'  => 'DAV',
            'Allow'          => 'OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT',
            'Accept-Ranges'  => 'bytes',
            'Content-Length' => '0',
            'X-Sabre-Version'=> DAV\Version::VERSION,
        ),$this->response->headers);

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('', $this->response->body);

    }

}
