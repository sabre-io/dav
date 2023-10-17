<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

class Issue33Test extends \PHPUnit\Framework\TestCase
{
    public function setup(): void
    {
        \Sabre\TestUtil::clearTempDir();
    }

    public function testCopyMoveInfo()
    {
        $bar = new SimpleCollection('bar');
        $root = new SimpleCollection('webdav', [$bar]);

        $server = new Server($root);
        $server->setBaseUri('/webdav/');

        $request = new HTTP\Request('GET', '/webdav/bar', [
            'Destination' => 'http://dev2.tribalos.com/webdav/%C3%A0fo%C3%B3',
            'Overwrite' => 'F',
        ]);

        $server->httpRequest = $request;

        $info = $server->getCopyAndMoveInfo($request);

        self::assertEquals('%C3%A0fo%C3%B3', urlencode($info['destination']));
        self::assertFalse($info['destinationExists']);
        self::assertFalse($info['destinationNode']);
    }

    public function testTreeMove()
    {
        mkdir(SABRE_TEMPDIR.'/issue33');
        $dir = new FS\Directory(SABRE_TEMPDIR.'/issue33');

        $dir->createDirectory('bar');

        $tree = new Tree($dir);
        $tree->move('bar', urldecode('%C3%A0fo%C3%B3'));

        $node = $tree->getNodeForPath(urldecode('%C3%A0fo%C3%B3'));
        self::assertEquals(urldecode('%C3%A0fo%C3%B3'), $node->getName());
    }

    public function testDirName()
    {
        $dirname1 = 'bar';
        $dirname2 = urlencode('%C3%A0fo%C3%B3');

        self::assertTrue(dirname($dirname1) == dirname($dirname2));
    }

    /**
     * @depends testTreeMove
     * @depends testCopyMoveInfo
     */
    public function testEverything()
    {
        $request = new HTTP\Request('MOVE', '/webdav/bar', [
            'Destination' => 'http://dev2.tribalos.com/webdav/%C3%A0fo%C3%B3',
            'Overwrite' => 'F',
        ]);

        $request->setBody('');

        $response = new HTTP\ResponseMock();

        // Server setup
        mkdir(SABRE_TEMPDIR.'/issue33');
        $dir = new FS\Directory(SABRE_TEMPDIR.'/issue33');

        $dir->createDirectory('bar');

        $tree = new Tree($dir);

        $server = new Server($tree);
        $server->setBaseUri('/webdav/');

        $server->httpRequest = $request;
        $server->httpResponse = $response;
        $server->sapi = new HTTP\SapiMock();
        $server->exec();

        self::assertTrue(file_exists(SABRE_TEMPDIR.'/issue33/'.urldecode('%C3%A0fo%C3%B3')));
    }
}
