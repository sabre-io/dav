<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

/**
 * This file tests HTTP requests that use the Range: header.
 *
 * @copyright Copyright (C) fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ServerRangeTest extends \Sabre\AbstractDAVServerTestCase
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

        $this->lastModified = HTTP\toDate(
            new \DateTime('@'.$this->server->tree->getNodeForPath('files/test.txt')->getLastModified())
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

        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [4],
            'Content-Range' => ['bytes 2-5/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );
        self::assertEquals(206, $response->getStatus());
        self::assertEquals('st c', $response->getBodyAsString());
    }

    /**
     * @depends testRange
     */
    public function testStartRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=2-']);
        $response = $this->request($request);

        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [11],
            'Content-Range' => ['bytes 2-12/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        self::assertEquals(206, $response->getStatus());
        self::assertEquals('st contents', $response->getBodyAsString());
    }

    /**
     * @depends testRange
     */
    public function testEndRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=-8']);
        $response = $this->request($request);

        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [8],
            'Content-Range' => ['bytes 5-12/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        self::assertEquals(206, $response->getStatus());
        self::assertEquals('contents', $response->getBodyAsString());
    }

    /**
     * @depends testRange
     */
    public function testTooHighRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=100-200']);
        $response = $this->request($request);

        self::assertEquals(416, $response->getStatus());
    }

    /**
     * @depends testRange
     */
    public function testCrazyRange()
    {
        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=8-4']);
        $response = $this->request($request);

        self::assertEquals(416, $response->getStatus());
    }

    public function testNonSeekableStream()
    {
        $request = new HTTP\Request('GET', '/files/no-seeking.txt', ['Range' => 'bytes=2-5']);
        $response = $this->request($request);

        self::assertEquals(206, $response->getStatus());
        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [4],
            'Content-Range' => ['bytes 2-5/12'],
            // 'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        self::assertEquals('st c', $response->getBodyAsString());
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

        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [4],
            'Content-Range' => ['bytes 2-5/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        self::assertEquals(206, $response->getStatus());
        self::assertEquals('st c', $response->getBodyAsString());
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

        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [13],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        self::assertEquals(200, $response->getStatus());
        self::assertEquals('Test contents', $response->getBodyAsString());
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

        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [4],
            'Content-Range' => ['bytes 2-5/13'],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        self::assertEquals(206, $response->getStatus());
        self::assertEquals('st c', $response->getBodyAsString());
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

        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [13],
            'ETag' => ['"'.md5('Test contents').'"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        self::assertEquals(200, $response->getStatus());
        self::assertEquals('Test contents', $response->getBodyAsString());
    }
}
