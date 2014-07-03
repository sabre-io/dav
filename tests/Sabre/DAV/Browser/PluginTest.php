<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer{

    protected $plugin;

    function setUp() {

        parent::setUp();
        $this->server->addPlugin($this->plugin = new Plugin());

    }

    function testCollectionGet() {

        $serverVars = array(
            'REQUEST_URI'    => '/dir',
            'REQUEST_METHOD' => 'GET',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(200, $this->response->status, "Incorrect status received. Full response body: " . $this->response->getBodyAsString());
        $this->assertEquals(array(
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Security-Policy' => "img-src 'self'; style-src 'self';"
            ),
            $this->response->headers
        );

        $this->assertTrue(strpos($this->response->body, '<title>dir/') !== false);
        $this->assertTrue(strpos($this->response->body, '<a href="/dir/child.txt">')!==false);

    }
    function testCollectionGetRoot() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'GET',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(200, $this->response->status, "Incorrect status received. Full response body: " . $this->response->getBodyAsString());
        $this->assertEquals(array(
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Security-Policy' => "img-src 'self'; style-src 'self';"
            ),
            $this->response->headers
        );

        $this->assertTrue(strpos($this->response->body, '<title>/') !== false);
        $this->assertTrue(strpos($this->response->body, '<a href="/dir/">')!==false);
        $this->assertTrue(strpos($this->response->body, '<span class="btn disabled">')!==false);

    }

    function testGETPassthru() {

        $request = new HTTP\Request('GET', '/random');
        $response = new HTTP\Response();
        $this->assertNull(
            $this->plugin->httpGet($request, $response)
        );

    }

    function testPostOtherContentType() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'text/xml',
        );
        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(501, $this->response->status);

    }

    function testPostNoSabreAction() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        );
        $postVars = array();

        $request = HTTP\Sapi::createFromServerArray($serverVars,$postVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(501, $this->response->status);

    }

    function testPostMkCol() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        );
        $postVars = array(
            'sabreAction' => 'mkcol',
            'name' => 'new_collection',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setPostData($postVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(302, $this->response->status);
        $this->assertEquals(array(
            'Location' => '/',
        ), $this->response->headers);

        $this->assertTrue(is_dir(SABRE_TEMPDIR . '/new_collection'));

    }

    function testGetAsset() {

        $request = new HTTP\Request('GET', '/?sabreAction=asset&assetName=favicon.ico');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(200, $this->response->getStatus(), 'Error: ' . $this->response->body);
        $this->assertEquals([
            'Content-Type' => 'image/vnd.microsoft.icon',
            'Content-Length' => '4286',
            'Cache-Control' => 'public, max-age=1209600',
        ], $this->response->getHeaders());

    }

    function testGetAsset404() {

        $request = new HTTP\Request('GET', '/?sabreAction=asset&assetName=flavicon.ico');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(404, $this->response->getStatus(), 'Error: ' . $this->response->body);

    }
}
