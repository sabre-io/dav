<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\DAVServerTest;
use Sabre\HTTP;

/**
 * Tests related to the MOVE request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HttpMoveTest extends DAVServerTest
{
    /**
     * Sets up the DAV tree.
     */
    public function setUpTree()
    {
        $this->tree = new Mock\Collection('root', [
            'file1' => 'content1',
            'file2' => 'content2',
        ]);
    }

    public function testMoveToSelf()
    {
        $request = new HTTP\Request('MOVE', '/file1', [
            'Destination' => '/file1',
        ]);
        $response = $this->request($request);
        self::assertEquals(403, $response->getStatus());
        self::assertEquals('content1', $this->tree->getChild('file1')->get());
    }

    public function testMove()
    {
        $request = new HTTP\Request('MOVE', '/file1', [
            'Destination' => '/file3',
        ]);
        $response = $this->request($request);
        self::assertEquals(201, $response->getStatus(), print_r($response, true));
        self::assertEquals('content1', $this->tree->getChild('file3')->get());
        self::assertFalse($this->tree->childExists('file1'));
    }

    public function testMoveToExisting()
    {
        $request = new HTTP\Request('MOVE', '/file1', [
            'Destination' => '/file2',
        ]);
        $response = $this->request($request);
        self::assertEquals(204, $response->getStatus(), print_r($response, true));
        self::assertEquals('content1', $this->tree->getChild('file2')->get());
        self::assertFalse($this->tree->childExists('file1'));
    }

    public function testMoveToExistingOverwriteT()
    {
        $request = new HTTP\Request('MOVE', '/file1', [
            'Destination' => '/file2',
            'Overwrite' => 'T',
        ]);
        $response = $this->request($request);
        self::assertEquals(204, $response->getStatus(), print_r($response, true));
        self::assertEquals('content1', $this->tree->getChild('file2')->get());
        self::assertFalse($this->tree->childExists('file1'));
    }

    public function testMoveToExistingOverwriteF()
    {
        $request = new HTTP\Request('MOVE', '/file1', [
            'Destination' => '/file2',
            'Overwrite' => 'F',
        ]);
        $response = $this->request($request);
        self::assertEquals(412, $response->getStatus(), print_r($response, true));
        self::assertEquals('content1', $this->tree->getChild('file1')->get());
        self::assertEquals('content2', $this->tree->getChild('file2')->get());
        self::assertTrue($this->tree->childExists('file1'));
        self::assertTrue($this->tree->childExists('file2'));
    }

    /**
     * If we MOVE to an existing file, but a plugin prevents the original from
     * being deleted, we need to make sure that the server does not delete
     * the destination.
     */
    public function testMoveToExistingBlockedDeleteSource()
    {
        $this->server->on('beforeUnbind', function ($path) {
            if ('file1' === $path) {
                throw new \Sabre\DAV\Exception\Forbidden('uh oh');
            }
        });
        $request = new HTTP\Request('MOVE', '/file1', [
            'Destination' => '/file2',
        ]);
        $response = $this->request($request);
        self::assertEquals(403, $response->getStatus(), print_r($response, true));
        self::assertEquals('content1', $this->tree->getChild('file1')->get());
        self::assertEquals('content2', $this->tree->getChild('file2')->get());
        self::assertTrue($this->tree->childExists('file1'));
        self::assertTrue($this->tree->childExists('file2'));
    }
}
