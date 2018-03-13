<?php declare (strict_types=1);

namespace Sabre\DAV\Browser;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class MapGetToPropFindTest extends DAV\AbstractServer {

    function setUp() {

        parent::setUp();
        $this->server->addPlugin(new MapGetToPropFind());

    }

    function testCollectionGet() {

        $request = new ServerRequest('GET', '/', [], '');
        $response = $this->server->handle($request);


        $this->assertEquals(207, $response->getStatusCode(), 'Incorrect status response received. Full response body: ' . $response->getBody()->getContents());
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            'DAV'             => ['1, 3, extended-mkcol'],
            'Vary'            => ['Brief,Prefer'],
            ],
            $response->getHeaders()
         );

    }


}
