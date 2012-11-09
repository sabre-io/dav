<?php

namespace Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class ServerRangeTest extends AbstractServer{

    protected function getRootNode() {

        return new FSExt\Directory(SABRE_TEMPDIR);

    }

    function testRange() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 4,
            'Content-Range' => 'bytes 2-5/13',
            'Last-Modified' => HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            'ETag'          => '"' . md5(file_get_contents(SABRE_TEMPDIR . '/test.txt')). '"',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 206 Partial Content',$this->response->status);
        $this->assertEquals('st c', stream_get_contents($this->response->body));

    }

    /**
     * @depends testRange
     */
    function testStartRange() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 11,
            'Content-Range' => 'bytes 2-12/13',
            'Last-Modified' => HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            'ETag'          => '"' . md5(file_get_contents(SABRE_TEMPDIR . '/test.txt')) . '"',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 206 Partial Content',$this->response->status);
        $this->assertEquals('st contents', stream_get_contents($this->response->body));

    }

    /**
     * @depends testRange
     */
    function testEndRange() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=-8',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 8,
            'Content-Range' => 'bytes 5-12/13',
            'Last-Modified' => HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            'ETag'          => '"' . md5(file_get_contents(SABRE_TEMPDIR . '/test.txt')). '"',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 206 Partial Content',$this->response->status);
        $this->assertEquals('contents', stream_get_contents($this->response->body));

    }

    /**
     * @depends testRange
     */
    function testTooHighRange() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=100-200',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 416 Requested Range Not Satisfiable',$this->response->status);

    }

    /**
     * @depends testRange
     */
    function testCrazyRange() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=8-4',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 416 Requested Range Not Satisfiable',$this->response->status);

    }

    /**
     * @depends testRange
     * @covers \Sabre\DAV\Server::httpGet
     */
    function testIfRangeEtag() {

        $node = $this->server->tree->getNodeForPath('test.txt');

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
            'HTTP_IF_RANGE'  => $node->getETag(),
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 4,
            'Content-Range' => 'bytes 2-5/13',
            'Last-Modified' => HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            'ETag'          => '"' . md5(file_get_contents(SABRE_TEMPDIR . '/test.txt')) . '"',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 206 Partial Content',$this->response->status);
        $this->assertEquals('st c', stream_get_contents($this->response->body));

    }

    /**
     * @depends testRange
     * @covers \Sabre\DAV\Server::httpGet
     */
    function testIfRangeEtagIncorrect() {

        $node = $this->server->tree->getNodeForPath('test.txt');

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
            'HTTP_IF_RANGE'  => $node->getETag() . 'blabla',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            'Last-Modified' => HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            'ETag'          => '"' . md5(file_get_contents(SABRE_TEMPDIR . '/test.txt')) . '"',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 200 OK',$this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }

    /**
     * @depends testRange
     * @covers \Sabre\DAV\Server::httpGet
     */
    function testIfRangeModificationDate() {

        $node = $this->server->tree->getNodeForPath('test.txt');

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
            'HTTP_IF_RANGE'  => 'tomorrow',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 4,
            'Content-Range' => 'bytes 2-5/13',
            'Last-Modified' => HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            'ETag'          => '"' . md5(file_get_contents(SABRE_TEMPDIR . '/test.txt')) . '"',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 206 Partial Content',$this->response->status);
        $this->assertEquals('st c', stream_get_contents($this->response->body));

    }

    /**
     * @depends testRange
     * @covers \Sabre\DAV\Server::httpGet
     */
    function testIfRangeModificationDateModified() {

        $node = $this->server->tree->getNodeForPath('test.txt');

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
            'HTTP_IF_RANGE'  => '-2 years',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            'Last-Modified' => HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            'ETag'          => '"' . md5(file_get_contents(SABRE_TEMPDIR . '/test.txt')) . '"',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 200 OK',$this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }
}
