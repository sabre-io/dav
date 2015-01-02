<?php

namespace Sabre\DAV;

use Sabre\DAVServerTest;
use Sabre\HTTP;

/**
 * Tests related to the PUT request.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HttpDeleteTest extends DAVServerTest {

    /**
     * Sets up the DAV tree.
     *
     * @return void
     */
    public function setUpTree() {

        $this->tree = new Mock\Collection('root', array(
            'file1' => 'foo',
            'dir' => array(
                'subfile' => 'bar',
                'subfile2' => 'baz',
            ),
        ));

    }

    /**
     * A successful DELETE
     */
    public function testDelete() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file1',
            'REQUEST_METHOD' => 'DELETE',
        ));

        $response = $this->request($request);

        $this->assertEquals(
            'HTTP/1.1 204 No Content',
            $response->status,
            "Incorrect status code. Response body:  " . $response->body
        );

        $this->assertEquals(
            array(
                'Content-Length' => '0',
            ),
            $response->headers
        );

    }

    /**
     * Deleting a Directory
     */
    public function testDeleteDirectory() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/dir',
            'REQUEST_METHOD' => 'DELETE',
        ));

        $response = $this->request($request);

        $this->assertEquals(
            'HTTP/1.1 204 No Content',
            $response->status,
            "Incorrect status code. Response body:  " . $response->body
        );

        $this->assertEquals(
            array(
                'Content-Length' => '0',
            ),
            $response->headers
        );

    }

    /**
     * DELETE on a node that does not exist
     */
    public function testDeleteNotFound() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file2',
            'REQUEST_METHOD' => 'DELETE',
        ));

        $response = $this->request($request);

        $this->assertEquals(
            'HTTP/1.1 404 Not Found',
            $response->status,
            "Incorrect status code. Response body:  " . $response->body
        );

    }

    /**
     * DELETE with preconditions
     */
    public function testDeletePreconditions() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file1',
            'REQUEST_METHOD' => 'DELETE',
            'HTTP_IF_MATCH' => '"' . md5('foo') . '"',
        ));

        $response = $this->request($request);

        $this->assertEquals(
            'HTTP/1.1 204 No Content',
            $response->status,
            "Incorrect status code. Response body:  " . $response->body
        );

    }

    /**
     * DELETE with incorrect preconditions
     */
    public function testDeletePreconditionsFailed() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file1',
            'REQUEST_METHOD' => 'DELETE',
            'HTTP_IF_MATCH' => '"' . md5('bar') . '"',
        ));

        $response = $this->request($request);

        $this->assertEquals(
            'HTTP/1.1 412 Precondition failed',
            $response->status,
            "Incorrect status code. Response body:  " . $response->body
        );

    }
}
