<?php

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/PartialUpdate/FileMock.php';

class PluginTest extends \Sabre\DAVServerTest {

    protected $node;
    protected $plugin;

    function setUp() {

        $this->node = new FileMock();
        $this->tree[] = $this->node;

        parent::setUp();

        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);



    }

    function testInit() {

        $this->assertEquals('partialupdate', $this->plugin->getPluginName());
        $this->assertEquals(['sabredav-partialupdate'], $this->plugin->getFeatures());
        $this->assertEquals([
            'PATCH'
        ], $this->plugin->getHTTPMethods('partial'));
        $this->assertEquals([
        ], $this->plugin->getHTTPMethods(''));

    }

    function testPatchNoRange() {

        $this->node->put('00000000');
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI'    => '/partial',
        ]);
        $response = $this->request($request);

        $this->assertEquals(400, $response->status, 'Full response body:' . $response->body);

    }

    function testPatchNotSupported() {

        $this->node->put('00000000');
        $request = new HTTP\Request('PATCH', '/', ['X-Update-Range' => '3-4']);
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals(405, $response->status, 'Full response body:' . $response->body);

    }

    function testPatchNoContentType() {

        $this->node->put('00000000');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-4']);
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals(415, $response->status, 'Full response body:' . $response->body);

    }

    function testPatchBadRange() {

        $this->node->put('00000000');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-4', 'Content-Type' => 'application/x-sabredav-partialupdate', 'Content-Length' => '3']);
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals(416, $response->status, 'Full response body:' . $response->body);

    }

    function testPatchNoLength() {

        $this->node->put('00000000');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-5', 'Content-Type' => 'application/x-sabredav-partialupdate']);
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals(411, $response->status, 'Full response body:' . $response->body);

    }

    function testPatchSuccess() {

        $this->node->put('00000000');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-5', 'Content-Type' => 'application/x-sabredav-partialupdate', 'Content-Length' => 3]);
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals(204, $response->status, 'Full response body:' . $response->body);
        $this->assertEquals('00011100', $this->node->get());

    }

    function testPatchNoEndRange() {

        $this->node->put('00000');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-', 'Content-Type' => 'application/x-sabredav-partialupdate', 'Content-Length' => '3']);
        $request->setBody(
            '111'
        );

        $response = $this->request($request);

        $this->assertEquals(204, $response->getStatus(), 'Full response body:' . $response->getBodyAsString());
        $this->assertEquals('00111', $this->node->get());

    }

}
