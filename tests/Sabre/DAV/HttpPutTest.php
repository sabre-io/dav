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
 * @covers Sabre\DAV\Server::httpPut
 * @covers Sabre\DAV\Server::createFile
 * @covers Sabre\DAV\Server::checkPreconditions
 */
class HttpPutTest extends DAVServerTest {

    /**
     * Sets up the DAV tree.
     *
     * @return void
     */
    public function setUpTree() {

        $this->tree = new Mock\Collection('root', array(
            'file1' => 'foo',
        ));

    }

    /**
     * A successful PUT of a new file.
     */
    public function testPut() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file2',
            'REQUEST_METHOD' => 'PUT',
        ));
        $request->setBody('hello');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 201 Created', $response->status);

        $this->assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file2')->get()
        );

        $this->assertEquals(
            array(
                'Content-Length' => '0',
                'ETag' => '"' . md5('hello') . '"'
            ),
            $response->headers
        );

    }

    /**
     * A successful PUT on an existing file.
     *
     * @depends testPut
     */
    public function testPutExisting() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file1',
            'REQUEST_METHOD' => 'PUT',
        ));
        $request->setBody('bar');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 204 No Content', $response->status);

        $this->assertEquals(
            'bar',
            $this->server->tree->getNodeForPath('file1')->get()
        );

        $this->assertEquals(
            array(
                'Content-Length' => '0',
                'ETag' => '"' . md5('bar') . '"'
            ),
            $response->headers
        );

    }

    /**
     * PUT on existing file with If-Match: *
     *
     * @depends testPutExisting
     */
    public function testPutExistingIfMatchStar() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file1',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF_MATCH' => '*',
        ));
        $request->setBody('hello');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 204 No Content', $response->status);

        $this->assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file1')->get()
        );

        $this->assertEquals(
            array(
                'Content-Length' => '0',
                'ETag' => '"' . md5('hello') . '"'
            ),
            $response->headers
        );

    }

    /**
     * PUT on existing file with If-Match: with a correct etag
     *
     * @depends testPutExisting
     */
    public function testPutExistingIfMatchCorrect() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file1',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF_MATCH' => '"' . md5('foo') . '"',
        ));
        $request->setBody('hello');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 204 No Content', $response->status);

        $this->assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file1')->get()
        );

        $this->assertEquals(
            array(
                'Content-Length' => '0',
                'ETag' => '"' . md5('hello') . '"'
            ),
            $response->headers
        );

    }

    /**
     * PUT with Content-Range should be rejected.
     *
     * @depends testPut
     */
    public function testPutContentRange() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file2',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_CONTENT_RANGE' => 'bytes/100-200',
        ));
        $request->setBody('hello');

        $response = $this->request($request);
        $this->assertEquals('HTTP/1.1 501 Not Implemented', $response->status);

    }

    /**
     * PUT on non-existing file with If-None-Match: * should work.
     *
     * @depends testPut
     */
    public function testPutIfNoneMatchStar() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file2',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF_NONE_MATCH' => '*',
        ));
        $request->setBody('hello');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 201 Created', $response->status);

        $this->assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file2')->get()
        );

        $this->assertEquals(
            array(
                'Content-Length' => '0',
                'ETag' => '"' . md5('hello') . '"'
            ),
            $response->headers
        );

    }

    /**
     * PUT on non-existing file with If-Match: * should fail.
     *
     * @depends testPut
     */
    public function testPutIfMatchStar() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file2',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF_MATCH' => '*',
        ));
        $request->setBody('hello');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 412 Precondition failed', $response->status);

    }

    /**
     * PUT on existing file with If-None-Match: * should fail.
     *
     * @depends testPut
     */
    public function testPutExistingIfNoneMatchStar() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file1',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF_NONE_MATCH' => '*',
        ));
        $request->setBody('hello');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 412 Precondition failed', $response->status);

    }

    /**
     * PUT thats created in a non-collection should be rejected.
     *
     * @depends testPut
     */
    public function testPutNoParent() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file1/file2',
            'REQUEST_METHOD' => 'PUT',
        ));
        $request->setBody('hello');

        $response = $this->request($request);
        $this->assertEquals('HTTP/1.1 409 Conflict', $response->status);

    }

    /**
     * Finder may sometimes make a request, which gets its content-body
     * stripped. We can't always prevent this from happening, but in some cases
     * we can detected this and return an error instead.
     *
     * @depends testPut
     */
    public function testFinderPutSuccess() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file2',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_X_EXPECTED_ENTITY_LENGTH' => '5',
        ));
        $request->setBody('hello');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 201 Created', $response->status);

        $this->assertEquals(
            'hello',
            $this->server->tree->getNodeForPath('file2')->get()
        );

        $this->assertEquals(
            array(
                'Content-Length' => '0',
                'ETag' => '"' . md5('hello') . '"'
            ),
            $response->headers
        );

    }

    /**
     * Same as the last one, but in this case we're mimicing a failed request.
     *
     * @depends testFinderPutSuccess
     */
    public function testFinderPutFail() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file2',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_X_EXPECTED_ENTITY_LENGTH' => '5',
        ));
        $request->setBody('');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status);

    }

    /**
     * Plugins can intercept PUT. We need to make sure that works.
     */
    public function testPutIntercept() {

        $this->server->subscribeEvent('beforeBind', array($this, 'beforeBind'));

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/file2',
            'REQUEST_METHOD' => 'PUT',
        ));
        $request->setBody('hello');

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 418 I\'m a teapot', $response->status);

        $this->assertFalse(
            $this->server->tree->nodeExists('file2')
        );

        $this->assertEquals(
            array(
            ),
            $response->headers
        );

    }

    public function beforeBind() {

        $this->server->httpResponse->sendStatus(418);
        return false;

    }

}
