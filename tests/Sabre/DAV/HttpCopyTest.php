<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\AbstractDAVServerTestCase;
use Sabre\HTTP;

/**
 * Tests related to the COPY request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HttpCopyTest extends AbstractDAVServerTestCase
{
    /**
     * Sets up the DAV tree.
     */
    public function setUpTree()
    {
        $propsCollection = new Mock\PropertiesCollection('propscoll', [
            'file3' => 'content3',
            'file4' => 'content4',
        ], [
            'my-prop' => 'my-value',
        ]);
        $propsCollection->failMode = 'updatepropstrue';
        $this->tree = new Mock\PropertiesCollection('root', [
            'file1' => 'content1',
            'file2' => 'content2',
            'coll1' => new Mock\Collection('coll1', [
                'file3' => 'content3',
                'file4' => 'content4',
            ]),
            'propscoll' => $propsCollection,
        ]);
    }

    public function testCopyFile()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file5',
            'Depth' => 'infinity',
        ]);
        $response = $this->request($request);
        self::assertEquals(201, $response->getStatus());
        self::assertEquals('content1', $this->tree->getChild('file5')->get());
    }

    public function testCopyFileToSelf()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file1',
        ]);
        $response = $this->request($request);
        self::assertEquals(403, $response->getStatus());
    }

    public function testCopyFileToExisting()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file2',
            'Depth' => 'infinity',
        ]);
        $response = $this->request($request);
        self::assertEquals(204, $response->getStatus());
        self::assertEquals('content1', $this->tree->getChild('file2')->get());
    }

    public function testCopyFileToExistingOverwriteT()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file2',
            'Depth' => 'infinity',
            'Overwrite' => 'T',
        ]);
        $response = $this->request($request);
        self::assertEquals(204, $response->getStatus());
        self::assertEquals('content1', $this->tree->getChild('file2')->get());
    }

    public function testCopyFileToExistingOverwriteBadValue()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file2',
            'Depth' => 'infinity',
            'Overwrite' => 'B',
        ]);
        $response = $this->request($request);
        self::assertEquals(400, $response->getStatus());
    }

    public function testCopyFileNonExistantParent()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/notfound/file2',
            'Depth' => 'infinity',
        ]);
        $response = $this->request($request);
        self::assertEquals(409, $response->getStatus());
    }

    public function testCopyFileToExistingOverwriteF()
    {
        $request = new HTTP\Request('COPY', '/file1', [
            'Destination' => '/file2',
            'Depth' => 'infinity',
            'Overwrite' => 'F',
        ]);
        $response = $this->request($request);
        self::assertEquals(412, $response->getStatus());
        self::assertEquals('content2', $this->tree->getChild('file2')->get());
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
            'Depth' => 'infinity',
            'Overwrite' => 'T',
        ]);
        $response = $this->request($request);

        // This checks if the destination file is intact.
        self::assertEquals('content2', $this->tree->getChild('file2')->get());
    }

    public function testCopyColl()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/coll2',
            'Depth' => 'infinity',
        ]);
        $response = $this->request($request);
        self::assertEquals(201, $response->getStatus());
        self::assertEquals('content3', $this->tree->getChild('coll2')->getChild('file3')->get());
    }

    public function testShallowCopyColl()
    {
        // Ensure proppatches are applied
        $this->tree->failMode = 'updatepropstrue';
        $request = new HTTP\Request('COPY', '/propscoll', [
            'Destination' => '/shallow-coll',
            'Depth' => '0',
        ]);
        $response = $this->request($request);
        // reset
        $this->tree->failMode = false;

        self::assertEquals(201, $response->getStatus());
        // The copied collection exists
        self::assertEquals(true, $this->tree->childExists('shallow-coll'));
        // But it does not contain children
        self::assertEquals([], $this->tree->getChild('shallow-coll')->getChildren());
        // But the properties are preserved
        self::assertEquals(['my-prop' => 'my-value'], $this->tree->getChild('shallow-coll')->getProperties([]));
    }

    public function testCopyCollToSelf()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/coll1',
            'Depth' => 'infinity',
        ]);
        $response = $this->request($request);
        self::assertEquals(403, $response->getStatus());
    }

    public function testCopyCollToExisting()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/file2',
            'Depth' => 'infinity',
        ]);
        $response = $this->request($request);
        self::assertEquals(204, $response->getStatus());
        self::assertEquals('content3', $this->tree->getChild('file2')->getChild('file3')->get());
    }

    public function testCopyCollToExistingOverwriteT()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/file2',
            'Depth' => 'infinity',
            'Overwrite' => 'T',
        ]);
        $response = $this->request($request);
        self::assertEquals(204, $response->getStatus());
        self::assertEquals('content3', $this->tree->getChild('file2')->getChild('file3')->get());
    }

    public function testCopyCollToExistingOverwriteF()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/file2',
            'Depth' => 'infinity',
            'Overwrite' => 'F',
        ]);
        $response = $this->request($request);
        self::assertEquals(412, $response->getStatus());
        self::assertEquals('content2', $this->tree->getChild('file2')->get());
    }

    public function testCopyCollIntoSubtree()
    {
        $request = new HTTP\Request('COPY', '/coll1', [
            'Destination' => '/coll1/subcol',
            'Depth' => 'infinity',
        ]);
        $response = $this->request($request);
        self::assertEquals(409, $response->getStatus());
    }
}
