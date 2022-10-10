<?php

declare(strict_types=1);

namespace Sabre\DAV;

use DateTime;
use Sabre\HTTP;

/**
 * This file tests HTTP requests that use the Range: header.
 *
 * @copyright Copyright (C) fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ServerRangeTest extends \Sabre\DAVServerTest
{
    protected $setupFiles = true;

    /**
     * We need this string a lot.
     */
    protected $lastModified;

    public function setup(): void
    {
        parent::setUp();
        $this->server->createFile('files/test.txt', 'Test contents');
        $this->server->createFile('files/rangetest.txt', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras massa quam, tempus a bibendum eget, accumsan et risus. Duis volutpat diam consectetur lorem scelerisque, vitae tristique massa tristique. Donec porta elementum condimentum. Duis fringilla, est sed tempus placerat, tortor tortor pulvinar lacus, in semper magna felis id nisi. Pellentesque eleifend augue elit, non hendrerit ex euismod at. Morbi a auctor mi. Suspendisse vel imperdiet lacus. Aenean auctor nulla urna, in sagittis felis venenatis fringilla. Nulla facilisi. Suspendisse nunc.');

        $this->lastModified = HTTP\toDate(
            new DateTime('@'.$this->server->tree->getNodeForPath('files/test.txt')->getLastModified())
        );

        $stream = popen('echo "Test contents"', 'r');
        $streamingFile = new Mock\StreamingFile(
                'no-seeking.txt',
                $stream
            );
        $streamingFile->setSize(12);
        $this->server->tree->getNodeForPath('files')->addNode($streamingFile);
    }

    public function testRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=2-5']);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [4],
            'Content-Range' => ['bytes 2-5/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );
        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('st c', $response->getBodyAsString());
    }

    /**
     * @depends testRange
     */
    public function testStartRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=2-']);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [11],
            'Content-Range' => ['bytes 2-12/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('st contents', $response->getBodyAsString());
    }

    /**
     * @depends testRange
     */
    public function testEndRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=-8']);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [8],
            'Content-Range' => ['bytes 5-12/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('contents', $response->getBodyAsString());
    }

    /**
     * @depends testRange
     */
    public function testTooHighRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=100-200']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());
    }

    /**
     * @depends testRange
     */
    public function testCrazyRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=8-4']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());
    }

    public function testNonSeekableStream()
    {
        $request = new HTTP\Request('GET', '/files/no-seeking.txt', ['Range' => 'bytes=2-5']);
        $response = $this->request($request);

        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [4],
            'Content-Range' => ['bytes 2-5/12'],
            // 'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals('st c', $response->getBodyAsString());
    }

    /**
     * @depends testNonSeekableStream
     */
    public function testNonSeekableExceedingRange()
    {
        $request = new HTTP\Request('GET', '/files/no-seeking.txt', ['Range' => 'bytes=100-200']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());
    }

    /**
     * @depends testRange
     */
    public function testIfRangeEtag()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', [
            'Range' => 'bytes=2-5',
            'If-Range' => '"'.md5('Test contents').'"',
        ]);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [4],
            'Content-Range' => ['bytes 2-5/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('st c', $response->getBodyAsString());
    }

    /**
     * @depends testIfRangeEtag
     */
    public function testIfRangeEtagIncorrect()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', [
            'Range' => 'bytes=2-5',
            'If-Range' => '"foobar"',
        ]);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [13],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('Test contents', $response->getBodyAsString());
    }

    /**
     * @depends testIfRangeEtag
     */
    public function testIfRangeModificationDate()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', [
            'Range' => 'bytes=2-5',
            'If-Range' => 'tomorrow',
        ]);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [4],
            'Content-Range' => ['bytes 2-5/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('st c', $response->getBodyAsString());
    }

    /**
     * @depends testIfRangeModificationDate
     */
    public function testIfRangeModificationDateModified()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', [
            'Range' => 'bytes=2-5',
            'If-Range' => '-2 years',
        ]);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [13],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('Test contents', $response->getBodyAsString());
    }

    // the following section contains new multipart range request tests
    // this string will be on top of every range. we will need this one a lot
    public function getBoundaryResponseString($boundary, $range)
    {
        return '--'.$boundary."\nContent-Type: application/octet-stream\nContent-Range: bytes ".$range[0].'-'.$range[1].'/'.$range[2]."\n\n";
    }

    public function testMultipartRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=2-5, 8-11']);
        $response = $this->request($request);

        $boundary = explode('boundary=', $response->getHeader('Content-Type'))[1];

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['multipart/byteranges; boundary='.$boundary],
            'Content-Length' => [219],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );
        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals($this->getBoundaryResponseString($boundary, [2, 5, 13]).'st c'."\n\n".$this->getBoundaryResponseString($boundary, [8, 11, 13]).'tent'."\n\n", $response->getBodyAsString());
    }

    /**
     * @depends testMultipartRange
     */
    public function testPartiallyValidRangeWithSingleValidRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=2-5, 11-8, 100-200, -5-5']);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [4],
            'Content-Range' => ['bytes 2-5/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );
        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('st c', $response->getBodyAsString());
    }

    /**
     * @depends testMultipartRange
     */
    public function testPartiallyValidRangeWithMultipleValidRanges()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=2-5, 8-11, 11-8, 100-200, -5-5']);
        $response = $this->request($request);

        $boundary = explode('boundary=', $response->getHeader('Content-Type'))[1];

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['multipart/byteranges; boundary='.$boundary],
            'Content-Length' => [219],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );
        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals($this->getBoundaryResponseString($boundary, [2, 5, 13]).'st c'."\n\n".$this->getBoundaryResponseString($boundary, [8, 11, 13]).'tent'."\n\n", $response->getBodyAsString());
    }

    /**
     * @depends testMultipartRange
     */
    public function testCompletelyInvalidRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=11-8, 100-200, -5-5, -']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());
    }

    /**
     * @depends testMultipartRange
     */
    public function testOverlappingRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=2-12, 3-5, 6-9, 9-11']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());
    }

    public function testTooManyRanges()
    {
        $ranges = [];
        for ($i = 0; $i < 513; ++$i) {
            $ranges[] = $i.'-'.$i;
        }
        $byterange = 'bytes='.implode(',', $ranges);

        $request = new HTTP\Request('GET', '/files/rangetest.txt', ['Range' => $byterange]);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());
    }

    public function testUnorderedRanges()
    {
        $ranges = [];
        for ($i = 0; $i < 18; ++$i) {
            $ranges[] = (20 - $i).'-'.(20 - $i);
        }
        $byterange = 'bytes='.implode(',', $ranges);

        $request = new HTTP\Request('GET', '/files/rangetest.txt', ['Range' => $byterange]);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());
    }

    public function testNonSeekableMultipartRange()
    {
        $request = new HTTP\Request('GET', '/files/no-seeking.txt', ['Range' => 'bytes=2-5, 7-11']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());
    }
}
