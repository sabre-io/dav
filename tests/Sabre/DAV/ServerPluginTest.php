<?php

require_once 'Sabre/DAV/AbstractServer.php';
require_once 'Sabre/DAV/TestPlugin.php';

class Sabre_DAV_ServerPluginTest extends Sabre_DAV_AbstractServer {

    protected $testPlugin;

    function setUp() {

        parent::setUp();
       
        $testPlugin = new Sabre_DAV_TestPlugin();
        $this->server->addPlugin($testPlugin);
        $this->testPlugin = $testPlugin;

    }

    function testOptions() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'OPTIONS',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'DAV'            => '1, 3, drinking',
            'MS-Author-Via'  => 'DAV',
            'Allow'          => 'OPTIONS GET HEAD DELETE TRACE PROPFIND MKCOL PUT PROPPATCH COPY MOVE REPORT BEER WINE',
            'Accept-Ranges'  => 'bytes',
            'Content-Length' =>  '0',
        ),$this->response->headers);

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertEquals('OPTIONS',$this->testPlugin->beforeMethod);

    
    }

}

?>
