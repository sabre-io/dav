<?php declare (strict_types=1);

namespace Sabre\DAV\PartialUpdate;

use GuzzleHttp\Psr7\ServerRequest;
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

        $this->node->put('aaaaaaaa');
        $request = new ServerRequest('PATCH', '/partial');
        $this->request($request, 400);
    }

    function testPatchNotSupported() {

        $this->node->put('aaaaaaaa');
        $request = new ServerRequest('PATCH', '/', ['X-Update-Range' => '3-4'], 'bbb');
        $this->request($request, 405);
    }

    function testPatchNoContentType() {

        $this->node->put('aaaaaaaa');
        $request = new ServerRequest('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-4'], 'bbb');
        $this->request($request, 415);
    }

    function testPatchBadRange() {

        $this->node->put('aaaaaaaa');
        $request = new ServerRequest('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-4', 'Content-Type' => 'application/x-sabredav-partialupdate', 'Content-Length' => '3'], 'bbb');
        $this->request($request, 416);
    }

    function testPatchNoLength() {

        $this->node->put('aaaaaaaa');
        $request = new ServerRequest('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-5', 'Content-Type' => 'application/x-sabredav-partialupdate'], 'bbb');
        $this->request($request, 411);
    }

    function testPatchSuccess() {

        $this->node->put('aaaaaaaa');
        $request = new ServerRequest('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-5', 'Content-Type' => 'application/x-sabredav-partialupdate', 'Content-Length' => 3], 'bbb');
        $this->request($request, 204);

        $this->assertEquals('aaabbbaa', $this->node->get());

    }

    function testPatchNoEndRange() {

        $this->node->put('aaaaa');
        $request = new ServerRequest('PATCH', '/partial', ['X-Update-Range' => 'bytes=3-', 'Content-Type' => 'application/x-sabredav-partialupdate', 'Content-Length' => '3'], 'bbb');

        $this->request($request, 204);

        $this->assertEquals('aaabbb', $this->node->get());

    }

}
