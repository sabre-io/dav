<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\HTTP;

require_once 'Sabre/TestUtil.php';

class Issue33Test extends \PHPUnit_Framework_TestCase {

    function setUp() {

        \Sabre\TestUtil::clearTempDir();

    }

    function testCopyMoveInfo() {

        $bar = new SimpleCollection('bar');
        $root = new SimpleCollection('webdav', [$bar]);

        $server = new Server($root);
        $server->setBaseUri('/webdav/');

        $request = new ServerRequest('GET', '/webdav/bar', [
            'Destination' => 'http://dev2.tribalos.com/webdav/%C3%A0fo%C3%B3',
            'Overwrite'   => 'F',
        ]);

        $info = $server->getCopyAndMoveInfo(new Psr7RequestWrapper($request));

        $this->assertEquals('%C3%A0fo%C3%B3', urlencode($info['destination']));
        $this->assertFalse($info['destinationExists']);
        $this->assertFalse($info['destinationNode']);

    }

    function testTreeMove() {

        mkdir(SABRE_TEMPDIR . '/issue33');
        $dir = new FS\Directory(SABRE_TEMPDIR . '/issue33');

        $dir->createDirectory('bar');

        $tree = new Tree($dir);
        $tree->move('bar', urldecode('%C3%A0fo%C3%B3'));

        $node = $tree->getNodeForPath(urldecode('%C3%A0fo%C3%B3'));
        $this->assertEquals(urldecode('%C3%A0fo%C3%B3'), $node->getName());

    }

    function testDirName() {

        $dirname1 = 'bar';
        $dirname2 = urlencode('%C3%A0fo%C3%B3');

        $this->assertTrue(dirname($dirname1) == dirname($dirname2));

    }

    /**
     * @depends testTreeMove
     * @depends testCopyMoveInfo
     */
    function testEverything() {

        $request = new ServerRequest('MOVE', '/webdav/bar', [
            'Destination' => 'http://dev2.tribalos.com/webdav/%C3%A0fo%C3%B3',
            'Overwrite'   => 'F',
        ], '');


        // Server setup
        mkdir(SABRE_TEMPDIR . '/issue33');
        $dir = new FS\Directory(SABRE_TEMPDIR . '/issue33');

        $dir->createDirectory('bar');

        $tree = new Tree($dir);

        $server = new Server($tree);
        $server->setBaseUri('/webdav/');
        $response = $server->handle($request);
        $this->assertEquals(201, $response->getStatusCode(), $response->getBody()->getContents());

        $this->assertTrue(file_exists(SABRE_TEMPDIR . '/issue33/' . urldecode('%C3%A0fo%C3%B3')));

    }

}
