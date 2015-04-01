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
class HttpGetTest extends DAVServerTest {

    /**
     * Sets up the DAV tree.
     *
     * @return void
     */
    function setUpTree() {

        $this->tree = new Mock\Collection('root', [
            'file1' => 'foo',
            new Mock\Collection('dir', []),
            new Mock\StreamingFile('streaming', 'stream') 
        ]);

    }

    function testGet() {

        $request = new HTTP\Request('GET', '/file1');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());

        // Removing Last-Modified because it keeps changing.
        $response->removeHeader('Last-Modified');

        $this->assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type'    => ['application/octet-stream'],
                'Content-Length'  => [3],
                'ETag'            => ['"' . md5('foo') . '"'],
            ],
            $response->getHeaders()
        );

        $this->assertEquals('foo', $response->getBodyAsString());

    }

    function testGetHttp10() {

        $request = new HTTP\Request('GET', '/file1');
        $request->setHttpVersion('1.0');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());

        // Removing Last-Modified because it keeps changing.
        $response->removeHeader('Last-Modified');

        $this->assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type'    => ['application/octet-stream'],
                'Content-Length'  => [3],
                'ETag'            => ['"' . md5('foo') . '"'],
            ],
            $response->getHeaders()
        );

        $this->assertEquals('1.0', $response->getHttpVersion());

        $this->assertEquals('foo', $response->getBodyAsString());

    }

    function testGet404() {

        $request = new HTTP\Request('GET', '/notfound');
        $response = $this->request($request);

        $this->assertEquals(404, $response->getStatus());

    }

    function testGet404_aswell() {

        $request = new HTTP\Request('GET', '/file1/subfile');
        $response = $this->request($request);

        $this->assertEquals(404, $response->getStatus());

    }

    /**
     * We automatically normalize double slashes.
     */
    function testGetDoubleSlash() {

        $request = new HTTP\Request('GET', '//file1');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());

        // Removing Last-Modified because it keeps changing.
        $response->removeHeader('Last-Modified');

        $this->assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type'    => ['application/octet-stream'],
                'Content-Length'  => [3],
                'ETag'            => ['"' . md5('foo') . '"'],
            ],
            $response->getHeaders()
        );

        $this->assertEquals('foo', $response->getBodyAsString());

    }

    /**
     * HEAD is identical to GET, but it's missing a body
     */
    function testHEAD() {

        $request = new HTTP\Request('HEAD', '//file1');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());

        // Removing Last-Modified because it keeps changing.
        $response->removeHeader('Last-Modified');

        $this->assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type'    => ['application/octet-stream'],
                'Content-Length'  => [3],
                'ETag'            => ['"' . md5('foo') . '"'],
            ],
            $response->getHeaders()
        );

        $this->assertEquals('', $response->getBodyAsString());

    }

    function testGetCollection() {

        $request = new HTTP\Request('GET', '/dir');
        $response = $this->request($request);

        $this->assertEquals(501, $response->getStatus());

    }

    /**
     * According to the specs, HEAD should behave identical to GET. But, broken
     * clients needs HEAD requests on collections to respond with a 200, so
     * that's what we do.
     */
    function testHEADCollection() {

        $request = new HTTP\Request('HEAD', '/dir');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());

    }

    function testGetStreaming() {

        $request = new HTTP\Request('GET', '/streaming');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());

        // Removing Last-Modified because it keeps changing.
        $response->removeHeader('Last-Modified');

        $this->assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type'    => ['application/octet-stream'],
            ],
            $response->getHeaders()
        );

        $this->assertEquals('stream', $response->getBodyAsString());

    }
}
