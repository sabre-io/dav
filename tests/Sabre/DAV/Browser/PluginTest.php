<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer{

    function setUp() {

        parent::setUp();
        $this->server->addPlugin(new Plugin());

    }

    function testCollectionGet() {

        $serverVars = array(
            'REQUEST_URI'    => '/dir',
            'REQUEST_METHOD' => 'GET',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals(array(
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Security-Policy' => "img-src 'self'; style-src 'unsafe-inline';"
            ),
            $this->response->headers
        );

        $this->assertTrue(strpos($this->response->body, 'Index for dir/') !== false);
        $this->assertTrue(strpos($this->response->body, '<a href="/dir/child.txt"><img src="/?sabreAction=asset&assetName=icons%2Ffile.png" alt="" width="24" />')!==false);

    }

    function testNotFound() {

        $serverVars = array(
            'REQUEST_URI'    => '/random',
            'REQUEST_METHOD' => 'GET',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(404, $this->response->status);

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

}
