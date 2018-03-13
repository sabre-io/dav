<?php declare (strict_types=1);

namespace Sabre\DAV;

use DateTime;
use GuzzleHttp\Psr7\ServerRequest;
use Sabre\HTTP;

/**
 * This file tests HTTP requests that use the Range: header.
 *
 * @copyright Copyright (C) fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ServerRangeTest extends \Sabre\DAVServerTest {

    protected $setupFiles = true;

    /**
     * We need this string a lot
     */
    protected $lastModified;

    function setUp() {

        parent::setUp();
        $this->server->createFile('files/test.txt', 'Test contents');

        $this->lastModified = HTTP\toDate(
            new DateTime('@' . $this->server->tree->getNodeForPath('files/test.txt')->getLastModified())
        );

        $stream = popen('echo "Test contents"', 'r');
        $streamingFile = new Mock\StreamingFile(
                'no-seeking.txt',
                $stream
            );
        $streamingFile->setSize(12);
        $this->server->tree->getNodeForPath('files')->addNode($streamingFile);

    }

    function testRange() {

        $request = new ServerRequest('GET', '/files/test.txt', ['Range' => 'bytes=2-5']);
        $response = $this->request($request);
        $responseBody = $response->getBody()->read($response->getHeaderLine('Content-Length'));

        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );
        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals('st c', $responseBody);

    }

    /**
     * @depends testRange
     */
    function testStartRange() {

        $request = new ServerRequest('GET', '/files/test.txt', ['Range' => 'bytes=2-']);
        $response = $this->request($request);

        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [11],
            'Content-Range'   => ['bytes 2-12/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals('st contents', $response->getBody()->getContents());

    }

    /**
     * @depends testRange
     */
    function testEndRange() {

        $request = new ServerRequest('GET', '/files/test.txt', ['Range' => 'bytes=-8']);
        $response = $this->request($request);

        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [8],
            'Content-Range'   => ['bytes 5-12/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals('contents', $response->getBody()->getContents());

    }

    /**
     * @depends testRange
     */
    function testTooHighRange() {

        $request = new ServerRequest('GET', '/files/test.txt', ['Range' => 'bytes=100-200']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatusCode());

    }

    /**
     * @depends testRange
     */
    function testCrazyRange() {

        $request = new ServerRequest('GET', '/files/test.txt', ['Range' => 'bytes=8-4']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatusCode());

    }

    function testNonSeekableStream() {

        $request = new ServerRequest('GET', '/files/no-seeking.txt', ['Range' => 'bytes=2-5']);
        $response = $this->request($request);

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/12'],
            // 'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals('st c', $response->getBody()->read($response->getHeaderLine('Content-Length')));

    }

    /**
     * @depends testRange
     */
    function testIfRangeEtag() {

        $request = new ServerRequest('GET', '/files/test.txt', [
            'Range'    => 'bytes=2-5',
            'If-Range' => '"' . md5('Test contents') . '"',
        ]);
        $response = $this->request($request);

        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals('st c', $response->getBody()->read($response->getHeaderLine('Content-Length')));

    }

    /**
     * @depends testIfRangeEtag
     */
    function testIfRangeEtagIncorrect() {

        $request = new ServerRequest('GET', '/files/test.txt', [
            'Range'    => 'bytes=2-5',
            'If-Range' => '"foobar"',
        ]);
        $response = $this->request($request);

        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Test contents', $response->getBody()->getContents());

    }

    /**
     * @depends testIfRangeEtag
     */
    function testIfRangeModificationDate() {

        $request = new ServerRequest('GET', '/files/test.txt', [
            'Range'    => 'bytes=2-5',
            'If-Range' => 'tomorrow',
        ]);
        $response = $this->request($request);

        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals('st c', $response->getBody()->read($response->getHeaderLine('Content-Length')));

    }

    /**
     * @depends testIfRangeModificationDate
     */
    function testIfRangeModificationDateModified() {

        $request = new ServerRequest('GET', '/files/test.txt', [
            'Range'    => 'bytes=2-5',
            'If-Range' => '-2 years',
        ]);
        $response = $this->request($request);

        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Test contents', $response->getBody()->getContents());

    }

}
