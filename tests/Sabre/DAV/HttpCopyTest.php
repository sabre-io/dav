<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\DAVServerTest;
use Sabre\HTTP;

/**
 * Tests related to the COPY request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HttpCopyTest extends DAVServerTest
{
    /**
     * Sets up the DAV tree.
     */
    public function setUpTree()
    {
        $this->tree = new Mock\Collection('root', [
            'file1' => 'content1',
            'file2' => 'content2',
            'coll1' => [
                'file3' => 'content3',
                'file4' => 'content4',
            ],
        ]);
    }

    public function testCopyFile()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file5',
        ]);
        $response = $this->request($request);
        $this->assertEquals(201, $response->getStatus());
        $this->assertEquals('content1', $this->tree->getChild('file5')->get());
    }

    public function testCopyFileToSelf()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file1',
        ]);
        $response = $this->request($request);
        $this->assertEquals(403, $response->getStatus());
    }

    public function testCopyFileToExisting()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file2',
        ]);
        $response = $this->request($request);
        $this->assertEquals(204, $response->getStatus());
        $this->assertEquals('content1', $this->tree->getChild('file2')->get());
    }

    public function testCopyFileToExistingOverwriteT()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file2',
            'Overwrite' => 'T',
        ]);
        $response = $this->request($request);
        $this->assertEquals(204, $response->getStatus());
        $this->assertEquals('content1', $this->tree->getChild('file2')->get());
    }

    public function testCopyFileToExistingOverwriteBadValue()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file2',
            'Overwrite' => 'B',
        ]);
        $response = $this->request($request);
        $this->assertEquals(400, $response->getStatus());
    }

    public function testCopyFileNonExistantParent()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/notfound/file2',
        ]);
        $response = $this->request($request);
        $this->assertEquals(409, $response->getStatus());
    }

    public function testCopyFileToExistingOverwriteF()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file2',
            'Overwrite' => 'F',
        ]);
        $response = $this->request($request);
        $this->assertEquals(412, $response->getStatus());
        $this->assertEquals('content2', $this->tree->getChild('file2')->get());
    }

    public function testCopyFileToExistinBlockedCreateDestination()
    {
        $this->server->on('beforeBind', function ($path) {
            if ('file2' === $path) {
                return false;
            }
        });
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file2',
            'Overwrite' => 'T',
        ]);
        $response = $this->request($request);

        // This checks if the destination file is intact.
        $this->assertEquals('content2', $this->tree->getChild('file2')->get());
    }

    public function testCopyColl()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/coll2',
        ]);
        $response = $this->request($request);
        $this->assertEquals(201, $response->getStatus());
        $this->assertEquals('content3', $this->tree->getChild('coll2')->getChild('file3')->get());
    }

    public function testCopyCollToSelf()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/coll1',
        ]);
        $response = $this->request($request);
        $this->assertEquals(403, $response->getStatus());
    }

    public function testCopyCollToExisting()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/file2',
        ]);
        $response = $this->request($request);
        $this->assertEquals(204, $response->getStatus());
        $this->assertEquals('content3', $this->tree->getChild('file2')->getChild('file3')->get());
    }

    public function testCopyCollToExistingOverwriteT()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/file2',
            'Overwrite' => 'T',
        ]);
        $response = $this->request($request);
        $this->assertEquals(204, $response->getStatus());
        $this->assertEquals('content3', $this->tree->getChild('file2')->getChild('file3')->get());
    }

    public function testCopyCollToExistingOverwriteF()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/file2',
            'Overwrite' => 'F',
        ]);
        $response = $this->request($request);
        $this->assertEquals(412, $response->getStatus());
        $this->assertEquals('content2', $this->tree->getChild('file2')->get());
    }

    public function testCopyCollIntoSubtree()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/coll1/subcol',
        ]);
        $response = $this->request($request);
        $this->assertEquals(409, $response->getStatus());
    }
}
