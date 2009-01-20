<?php

require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_DAV_ServerCopyMoveTest extends PHPUnit_Framework_TestCase {

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
        file_put_contents($this->tempDir . '/test2.txt', 'Test contents2');
        mkdir($this->tempDir . '/col');
        file_put_contents($this->tempDir . 'col/test.txt', 'Test contents');

    }

    function tearDown() {

        $cleanUp = array('test.txt','testput.txt','testcol','test2.txt','test3.txt','col/test.txt','col','col2/test.txt','col2');
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


    function testCopyOverWrite() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'COPY',
            'HTTP_DESTINATION' => '/test2.txt',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 204 No Content',$this->response->status);
        $this->assertEquals('Test contents',file_get_contents($this->tempDir . '/test2.txt'));

    }

    function testMoveOverWrite() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'MOVE',
            'HTTP_DESTINATION' => '/test2.txt',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 204 No Content',$this->response->status);
        $this->assertEquals('Test contents',file_get_contents($this->tempDir . '/test2.txt'));
        $this->assertFalse(file_exists($this->tempDir . '/test.txt'),'The sourcefile test.txt should no longer exist at this point');

    }

    function testBlockedOverWrite() {

        $serverVars = array(
            'REQUEST_URI'      => '/test.txt',
            'REQUEST_METHOD'   => 'COPY',
            'HTTP_DESTINATION' => '/test2.txt',
            'HTTP_OVERWRITE'   => 'F',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 412 Precondition failed',$this->response->status);
        $this->assertEquals('Test contents2',file_get_contents($this->tempDir . '/test2.txt'));


    }

    function testNonExistantParent() {

        $serverVars = array(
            'REQUEST_URI'      => '/test.txt',
            'REQUEST_METHOD'   => 'COPY',
            'HTTP_DESTINATION' => '/testcol2/test2.txt',
            'HTTP_OVERWRITE'   => 'F',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 409 Conflict',$this->response->status);

    }

    function testCopyDirectory() {
        
        $serverVars = array(
            'REQUEST_URI'    => '/col',
            'REQUEST_METHOD' => 'COPY',
            'HTTP_DESTINATION' => '/col2',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setHTTPRequest($request);
        $this->server->exec();

        $this->assertEquals(array(
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals('Test contents',file_get_contents($this->tempDir . '/col2/test.txt'));

    }


}

?>
