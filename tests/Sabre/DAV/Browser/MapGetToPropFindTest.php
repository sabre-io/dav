<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class MapGetToPropFindTest extends DAV\AbstractServer {

    function setUp() {

        parent::setUp();
        $this->server->addPlugin(new MapGetToPropFind());

    }

    function testCollectionGet() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new HTTP\Request($serverVars);
        $request->setBody('');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/xml; charset=utf-8',
            'DAV' => '1, 3, extended-mkcol',
            'Vary' => 'Brief,Prefer',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 207 Multi-Status',$this->response->status,'Incorrect status response received. Full response body: ' . $this->response->body);

    }


}
