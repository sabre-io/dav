<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\DAVServerTest;
use Sabre\HTTP;

/**
 * Tests related to the PUT request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HttpPutTest extends DAVServerTest
{
    /**
     * Sets up the DAV tree.
     */
    public function setUpTree()
    {
        $this->tree = new Mock\Collection('root', [
            'file1' => 'foo',
        ]);
    }

    /**
     * A successful PUT of a new file.
     */
    public function testPut()
    {
        $request = new HTTP\Request('PUT', '/file2', [], 'hello');

        $response = $this->request($request);

        self::assertEquals(201, $response->getStatus(), 'Incorrect status code received. Full response body:'.$response->getBodyAsString());

        self::assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file2')->get()
        );

        self::assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
                'ETag' => ['"'.md5('hello').'"'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * A successful PUT on an existing file.
     *
     * @depends testPut
     */
    public function testPutExisting()
    {
        $request = new HTTP\Request('PUT', '/file1', [], 'bar');

        $response = $this->request($request);

        self::assertEquals(204, $response->getStatus());

        self::assertEquals(
            'bar',
            $this->server->tree->getNodeForPath('file1')->get()
        );

        self::assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
                'ETag' => ['"'.md5('bar').'"'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * PUT on existing file with If-Match: *.
     *
     * @depends testPutExisting
     */
    public function testPutExistingIfMatchStar()
    {
        $request = new HTTP\Request(
            'PUT',
            '/file1',
            ['If-Match' => '*'],
            'hello'
        );

        $response = $this->request($request);

        self::assertEquals(204, $response->getStatus());

        self::assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file1')->get()
        );

        self::assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
                'ETag' => ['"'.md5('hello').'"'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * PUT on existing file with If-Match: with a correct etag.
     *
     * @depends testPutExisting
     */
    public function testPutExistingIfMatchCorrect()
    {
        $request = new HTTP\Request(
            'PUT',
            '/file1',
            ['If-Match' => '"'.md5('foo').'"'],
            'hello'
        );

        $response = $this->request($request);

        self::assertEquals(204, $response->status);

        self::assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file1')->get()
        );

        self::assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
                'ETag' => ['"'.md5('hello').'"'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * PUT with Content-Range should be rejected.
     *
     * @depends testPut
     */
    public function testPutContentRange()
    {
        $request = new HTTP\Request(
            'PUT',
            '/file2',
            ['Content-Range' => 'bytes/100-200'],
            'hello'
        );

        $response = $this->request($request);
        self::assertEquals(400, $response->getStatus());
    }

    /**
     * PUT on non-existing file with If-None-Match: * should work.
     *
     * @depends testPut
     */
    public function testPutIfNoneMatchStar()
    {
        $request = new HTTP\Request(
            'PUT',
            '/file2',
            ['If-None-Match' => '*'],
            'hello'
        );

        $response = $this->request($request);

        self::assertEquals(201, $response->getStatus());

        self::assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file2')->get()
        );

        self::assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
                'ETag' => ['"'.md5('hello').'"'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * PUT on non-existing file with If-Match: * should fail.
     *
     * @depends testPut
     */
    public function testPutIfMatchStar()
    {
        $request = new HTTP\Request(
            'PUT',
            '/file2',
            ['If-Match' => '*'],
            'hello'
        );

        $response = $this->request($request);

        self::assertEquals(412, $response->getStatus());
    }

    /**
     * PUT on existing file with If-None-Match: * should fail.
     *
     * @depends testPut
     */
    public function testPutExistingIfNoneMatchStar()
    {
        $request = new HTTP\Request(
            'PUT',
            '/file1',
            ['If-None-Match' => '*'],
            'hello'
        );
        $request->setBody('hello');

        $response = $this->request($request);

        self::assertEquals(412, $response->getStatus());
    }

    /**
     * PUT thats created in a non-collection should be rejected.
     *
     * @depends testPut
     */
    public function testPutParentIsNotCollection()
    {
        $request = new HTTP\Request(
            'PUT',
            '/file1/file2',
            [],
            'hello'
        );

        $response = $this->request($request);
        self::assertEquals(409, $response->getStatus());
    }

    /**
     * PUT thats created in a non-existent collection should be rejected.
     *
     * @depends testPut
     */
    public function testPutParentCollectionDoesNotExist()
    {
        $request = new HTTP\Request(
            'PUT',
            '/non-existent-collection/file2',
            [],
            'hello'
        );

        $response = $this->request($request);
        self::assertEquals(409, $response->getStatus());
    }

    /**
     * Finder may sometimes make a request, which gets its content-body
     * stripped. We can't always prevent this from happening, but in some cases
     * we can detected this and return an error instead.
     *
     * @depends testPut
     */
    public function testFinderPutSuccess()
    {
        $request = new HTTP\Request(
            'PUT',
            '/file2',
            ['X-Expected-Entity-Length' => '5'],
            'hello'
        );
        $response = $this->request($request);

        self::assertEquals(201, $response->getStatus());

        self::assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file2')->get()
        );

        self::assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
                'ETag' => ['"'.md5('hello').'"'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * Same as the last one, but in this case we're mimicing a failed request.
     *
     * @depends testFinderPutSuccess
     */
    public function testFinderPutFail()
    {
        $request = new HTTP\Request(
            'PUT',
            '/file2',
            ['X-Expected-Entity-Length' => '5'],
            ''
        );

        $response = $this->request($request);

        self::assertEquals(403, $response->getStatus());
    }

    /**
     * Plugins can intercept PUT. We need to make sure that works.
     *
     * @depends testPut
     */
    public function testPutIntercept()
    {
        $this->server->on('beforeBind', function ($uri) {
            $this->server->httpResponse->setStatus(418);

            return false;
        });

        $request = new HTTP\Request('PUT', '/file2', [], 'hello');
        $response = $this->request($request);

        self::assertEquals(418, $response->getStatus(), 'Incorrect status code received. Full response body: '.$response->getBodyAsString());

        self::assertFalse(
            $this->server->tree->nodeExists('file2')
        );

        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
        ], $response->getHeaders());
    }
}
