<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Sabre\DAVServerTest;

/**
 * Tests related to the GET request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
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

        $request = new ServerRequest('GET', '/file1');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatusCode());

        // Removing Last-Modified because it keeps changing.
        $headers = $response->getHeaders();
        unset($headers['Last-Modified']);

        $this->assertEquals(
            [

                'Content-Type'    => ['application/octet-stream'],
                'Content-Length'  => [3],
                'ETag'            => ['"' . md5('foo') . '"'],
            ],
            $headers
        );

        $this->assertEquals('foo', $response->getBody()->getContents());

    }

    function testGetHttp10() {

        $request = (new ServerRequest('GET', '/file1'))
            ->withProtocolVersion('1.0');

        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatusCode());

        // Removing Last-Modified because it keeps changing.
        $headers = $response->getHeaders();
        unset($headers['Last-Modified']);

        $this->assertEquals(
            [

                'Content-Type'    => ['application/octet-stream'],
                'Content-Length'  => [3],
                'ETag'            => ['"' . md5('foo') . '"'],
            ],
            $headers
        );

        $this->assertEquals('1.0', $response->getProtocolVersion());

        $this->assertEquals('foo', $response->getBody()->getContents());

    }

    function testGet404() {

        $request = new ServerRequest('GET', '/notfound');
        $response = $this->request($request);

        $this->assertEquals(404, $response->getStatusCode());

    }

    function testGet404_aswell() {

        $request = new ServerRequest('GET', '/file1/subfile');
        $response = $this->request($request);

        $this->assertEquals(404, $response->getStatusCode());

    }

    /**
     * We automatically normalize double slashes.
     */
    function testGetDoubleSlash() {

        $request = new ServerRequest('GET', 'http://localhost//file1');
        $this->assertNotEmpty($request->getUri()->getPath());

        $response = $this->request($request, 200);


        // Removing Last-Modified because it keeps changing.
        $headers = $response->getHeaders();
        unset($headers['Last-Modified']);

        $this->assertEquals(
            [

                'Content-Type'    => ['application/octet-stream'],
                'Content-Length'  => [3],
                'ETag'            => ['"' . md5('foo') . '"'],
            ],
            $headers
        );

        $this->assertEquals('foo', $response->getBody()->getContents());

    }

    function testGetCollection() {

        $request = new ServerRequest('GET', '/dir');
        $response = $this->request($request);

        $this->assertEquals(501, $response->getStatusCode());

    }

    function testGetStreaming() {

        $request = new ServerRequest('GET', '/streaming');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatusCode());

        // Removing Last-Modified because it keeps changing.
        $headers = $response->getHeaders();
        unset($headers['Last-Modified']);

        $this->assertEquals(
            [

                'Content-Type'    => ['application/octet-stream'],
            ],
            $headers
        );

        $this->assertEquals('stream',$response->getBody()->getContents());

    }
}
