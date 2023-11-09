<?php

declare(strict_types=1);

namespace Sabre\DAV\PartialUpdate;

use Sabre\HTTP;

class PluginTest extends \Sabre\AbstractDAVServerTestCase
{
    protected $node;
    protected $plugin;

    public function setup(): void
    {
        $this->node = new FileMock();
        $this->tree[] = $this->node;

        parent::setUp();

        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);
    }

    public function testInit()
    {
        self::assertEquals('partialupdate', $this->plugin->getPluginName());
        self::assertEquals(['sabredav-partialupdate'], $this->plugin->getFeatures());
        self::assertEquals([
            'PATCH',
        ], $this->plugin->getHTTPMethods('partial'));
        self::assertEquals([
        ], $this->plugin->getHTTPMethods(''));
    }

    public function testPatchNoRange()
    {
        $this->node->put('aaaaaaaa');
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI' => '/partial',
        ]);
        $response = $this->request($request);

        self::assertEquals(400, $response->status, 'Full response body:'.$response->getBodyAsString());
    }

    public function testPatchNotSupported()
    {
        $this->node->put('aaaaaaaa');
        $request = new HTTP\Request('PATCH', '/', ['X-Update-Range' => '3-4']);
        $request->setBody(
            'bbb'
        );
        $response = $this->request($request);

        self::assertEquals(405, $response->status, 'Full response body:'.$response->getBodyAsString());
    }

    public function testPatchNoContentType()
    {
        $this->node->put('aaaaaaaa');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-4']);
        $request->setBody(
            'bbb'
        );
        $response = $this->request($request);

        self::assertEquals(415, $response->status, 'Full response body:'.$response->getBodyAsString());
    }

    public function testPatchBadRange()
    {
        $this->node->put('aaaaaaaa');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-4', 'Content-Type' => 'application/x-sabredav-partialupdate', 'Content-Length' => '3']);
        $request->setBody(
            'bbb'
        );
        $response = $this->request($request);

        self::assertEquals(416, $response->status, 'Full response body:'.$response->getBodyAsString());
    }

    public function testPatchNoLength()
    {
        $this->node->put('aaaaaaaa');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-5', 'Content-Type' => 'application/x-sabredav-partialupdate']);
        $request->setBody(
            'bbb'
        );
        $response = $this->request($request);

        self::assertEquals(411, $response->status, 'Full response body:'.$response->getBodyAsString());
    }

    public function testPatchSuccess()
    {
        $this->node->put('aaaaaaaa');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-5', 'Content-Type' => 'application/x-sabredav-partialupdate', 'Content-Length' => 3]);
        $request->setBody(
            'bbb'
        );
        $response = $this->request($request);

        self::assertEquals(204, $response->status, 'Full response body:'.$response->getBodyAsString());
        self::assertEquals('aaabbbaa', $this->node->get());
    }

    public function testPatchNoEndRange()
    {
        $this->node->put('aaaaa');
        $request = new HTTP\Request('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-', 'Content-Type' => 'application/x-sabredav-partialupdate', 'Content-Length' => '3']);
        $request->setBody(
            'bbb'
        );

        $response = $this->request($request);

        self::assertEquals(204, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());
        self::assertEquals('aaabbb', $this->node->get());
    }
}
