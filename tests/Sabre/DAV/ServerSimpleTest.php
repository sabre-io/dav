<?php

require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_DAV_ServerSimpleTest extends PHPUnit_Framework_TestCase {

    private $response;
    private $request;
    private $server;
    private $tempDir = 'temp/';

    function setUp() {

        $this->response = new Sabre_HTTP_ResponseMock();
        $dir = new Sabre_DAV_FS_Directory($this->tempDir);
        $tree = new Sabre_DAV_ObjectTree($dir);
        $this->server = new Sabre_DAV_Server($tree);
        $this->server->setHTTPResponse($this->response);

        file_put_contents($this->tempDir . '/test.txt', 'Test contents');

    }

    function tearDown() {

        $cleanUp = array('test.txt','testput.txt','testcol');
        foreach($cleanUp as $file) {
            $tmpFile = $this->tempDir . '/' . $file;
            if (file_exists($tmpFile)) {
               
                if (is_dir($tmpFile)) {
                    rmdir($tmpFile);
                } else {
                    unlink($tmpFile);
                }

            }
        }

    }


    function testGet() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);
        $this->assertEquals('Test contents', $this->response->body);

    }

    function testHEAD() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'HEAD',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);
        $this->assertEquals('', $this->response->body);

    }

    function testPut() {

        $serverVars = array(
            'REQUEST_URI'    => '/testput.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing new file');
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(),$this->response->headers);

        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertEquals('Testing new file',file_get_contents($this->tempDir . '/testput.txt'));

    }

    function testPutAlreadyExists() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF_NONE_MATCH' => '*',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing new file');
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(),$this->response->headers);

        $this->assertEquals('HTTP/1.1 412 Precondition failed',$this->response->status);
        $this->assertNotEquals('Testing new file',file_get_contents($this->tempDir . '/test.txt'));

    }

    function testMkcol() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(),$this->response->headers);

        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertTrue(is_dir($this->tempDir . '/testcol'));

    }

    function testPutUpdate() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing updated file');
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(),$this->response->headers);

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertEquals('Testing updated file',file_get_contents($this->tempDir . '/test.txt'));

    }

    function testDelete() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'DELETE',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing updated file');
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(),$this->response->headers);

        $this->assertEquals('HTTP/1.1 204 No Content',$this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertFalse(file_exists($this->tempDir . '/test.txt'));

    }

    function testOptions() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'OPTIONS',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(
            'DAV'           => '1,3',
            'MS-Author-Via' => 'DAV',
            'Allow'         => 'OPTIONS GET HEAD POST DELETE TRACE PROPFIND MKCOL PUT PROPPATCH COPY MOVE',
        ),$this->response->headers);

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);
        $this->assertEquals('', $this->response->body);

    
    }
    function testNonExistantMethod() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'BLABLA',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(),$this->response->headers);

        $this->assertEquals('HTTP/1.1 501 Method not implemented',$this->response->status);


    }

}

?>
