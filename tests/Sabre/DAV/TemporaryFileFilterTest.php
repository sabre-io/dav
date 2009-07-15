<?php

class Sabre_DAV_TemporaryFileFilterTest extends Sabre_DAV_AbstractServer {

    function setUp() {

        parent::setUp();
        $plugin = new Sabre_DAV_TemporaryFileFilterPlugin('temp/tff');
        $this->server->addPlugin($plugin);

    }

    function testPutNormal() {

        $serverVars = array(
            'REQUEST_URI'    => '/testput.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals(array(),$this->response->headers);

        $this->assertEquals('Testing new file',file_get_contents($this->tempDir . '/testput.txt'));

    }

    function testPutTemp() {

        // mimicking an OS/X resource fork
        $serverVars = array(
            'REQUEST_URI'    => '/._testput.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals(array(
            'X-Sabre-Temp' => 'true',
        ),$this->response->headers);

        $this->assertFalse(file_exists('/._testput.txt'),'._testput.txt should not exist in the regular file structure.');

    }

}
