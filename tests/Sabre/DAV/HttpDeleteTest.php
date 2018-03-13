<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAVServerTest;
use Sabre\HTTP;

/**
 * Tests related to the PUT request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HttpDeleteTest extends DAVServerTest {

    /**
     * Sets up the DAV tree.
     *
     * @return void
     */
    function setUpTree() {

        $this->tree = new Mock\Collection('root', [
            'file1' => 'foo',
            'dir'   => [
                'subfile'  => 'bar',
                'subfile2' => 'baz',
            ],
        ]);

    }

    /**
     * A successful DELETE
     */
    function testDelete() {

        $request = new ServerRequest('DELETE', '/file1');

        $response = $this->request($request);

        $this->assertEquals(
            204,
            $response->getStatusCode(),
            "Incorrect status code. Response body:  " . $response->getBody()->getContents()
        );

        $this->assertEquals(
            [

                'Content-Length'  => ['0'],
            ],
            $response->getHeaders()
        );

    }

    /**
     * Deleting a Directory
     */
    function testDeleteDirectory() {

        $request = new ServerRequest('DELETE', '/dir');

        $response = $this->request($request);

        $this->assertEquals(
            204,
            $response->getStatusCode(),
            "Incorrect status code. Response body:  " . $response->getBody()->getContents()
        );

        $this->assertEquals(
            [

                'Content-Length'  => ['0'],
            ],
            $response->getHeaders()
        );

    }

    /**
     * DELETE on a node that does not exist
     */
    function testDeleteNotFound() {

        $request = new ServerRequest('DELETE', '/file2');
        $response = $this->request($request);

        $this->assertEquals(
            404,
            $response->getStatusCode(),
            "Incorrect status code. Response body:  " . $response->getBody()->getContents()
        );

    }

    /**
     * DELETE with preconditions
     */
    function testDeletePreconditions() {

        $request = new ServerRequest('DELETE', '/file1', [
            'If-Match' => '"' . md5('foo') . '"',
        ]);

        $response = $this->request($request);

        $this->assertEquals(
            204,
            $response->getStatusCode(),
            "Incorrect status code. Response body:  " . $response->getBody()->getContents()
        );

    }

    /**
     * DELETE with incorrect preconditions
     */
    function testDeletePreconditionsFailed() {

        $request = new ServerRequest('DELETE', '/file1', [
            'If-Match' => '"' . md5('bar') . '"',
        ]);

        $response = $this->request($request);

        $this->assertEquals(
            412,
            $response->getStatusCode(),
            "Incorrect status code. Response body:  " . $response->getBody()->getContents()
        );

    }
}
