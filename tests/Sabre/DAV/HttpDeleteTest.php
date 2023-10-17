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
class HttpDeleteTest extends DAVServerTest
{
    /**
     * Sets up the DAV tree.
     */
    public function setUpTree()
    {
        $this->tree = new Mock\Collection('root', [
            'file1' => 'foo',
            'dir' => [
                'subfile' => 'bar',
                'subfile2' => 'baz',
            ],
        ]);
    }

    /**
     * A successful DELETE.
     */
    public function testDelete()
    {
        $request = new HTTP\Request('DELETE', '/file1');

        $response = $this->request($request);

        self::assertEquals(
            204,
            $response->getStatus(),
            'Incorrect status code. Response body:  '.$response->getBodyAsString()
        );

        self::assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * Deleting a Directory.
     */
    public function testDeleteDirectory()
    {
        $request = new HTTP\Request('DELETE', '/dir');

        $response = $this->request($request);

        self::assertEquals(
            204,
            $response->getStatus(),
            'Incorrect status code. Response body:  '.$response->getBodyAsString()
        );

        self::assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
            ],
            $response->getHeaders()
        );
    }

    /**
     * DELETE on a node that does not exist.
     */
    public function testDeleteNotFound()
    {
        $request = new HTTP\Request('DELETE', '/file2');
        $response = $this->request($request);

        self::assertEquals(
            404,
            $response->getStatus(),
            'Incorrect status code. Response body:  '.$response->getBodyAsString()
        );
    }

    /**
     * DELETE with preconditions.
     */
    public function testDeletePreconditions()
    {
        $request = new HTTP\Request('DELETE', '/file1', [
            'If-Match' => '"'.md5('foo').'"',
        ]);

        $response = $this->request($request);

        self::assertEquals(
            204,
            $response->getStatus(),
            'Incorrect status code. Response body:  '.$response->getBodyAsString()
        );
    }

    /**
     * DELETE with incorrect preconditions.
     */
    public function testDeletePreconditionsFailed()
    {
        $request = new HTTP\Request('DELETE', '/file1', [
            'If-Match' => '"'.md5('bar').'"',
        ]);

        $response = $this->request($request);

        self::assertEquals(
            412,
            $response->getStatus(),
            'Incorrect status code. Response body:  '.$response->getBodyAsString()
        );
    }
}
