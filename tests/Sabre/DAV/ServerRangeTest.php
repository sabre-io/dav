<?php

require_once 'Sabre/DAV/AbstractServer.php';

class Sabre_DAV_ServerRangeTest extends Sabre_DAV_AbstractServer{

    function testRange() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5', 
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 4,
            'Content-Range' => 'bytes 2-5/13',
            'Last-Modified' => date(DateTime::RFC1123,filemtime($this->tempDir . '/test.txt')),
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 206 Partial Content',$this->response->status);
        $this->assertEquals('st c', stream_get_contents($this->response->body));

    }

    function testStartRange() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-', 
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 11,
            'Content-Range' => 'bytes 2-12/13',
            'Last-Modified' => date(DateTime::RFC1123,filemtime($this->tempDir . '/test.txt')),
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 206 Partial Content',$this->response->status);
        $this->assertEquals('st contents', stream_get_contents($this->response->body));

    }

    function testEndRange() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=-8', 
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 8,
            'Content-Range' => 'bytes 5-12/13',
            'Last-Modified' => date(DateTime::RFC1123,filemtime($this->tempDir . '/test.txt')),
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 206 Partial Content',$this->response->status);
        $this->assertEquals('contents', stream_get_contents($this->response->body));

    }

    function testTooHighRange() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=100-200', 
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 416 Requested Range Not Satisfiable',$this->response->status);

    }

    function testCrazyRange() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=8-4', 
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 416 Requested Range Not Satisfiable',$this->response->status);

    }

}

?>
