<?php

namespace Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class ServerRangeTest extends AbstractServer{

    protected function getRootNode() {

        return new FSExt\Directory(SABRE_TEMPDIR);

    }

    function testRange() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
        ];
        $filename = SABRE_TEMPDIR . '/test.txt';

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/13'],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(206, $this->response->status);
        $this->assertEquals('st c', stream_get_contents($this->response->body, 4));

    }

    /**
     * @depends testRange
     */
    function testStartRange() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-',
        ];
        $filename = SABRE_TEMPDIR . '/test.txt';

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [11],
            'Content-Range'   => ['bytes 2-12/13'],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(206, $this->response->status);
        $this->assertEquals('st contents', stream_get_contents($this->response->body, 11));

    }

    /**
     * @depends testRange
     */
    function testEndRange() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=-8',
        ];
        $filename = SABRE_TEMPDIR . '/test.txt';

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [8],
            'Content-Range'   => ['bytes 5-12/13'],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(206, $this->response->status);
        $this->assertEquals('contents', stream_get_contents($this->response->body, 8));

    }

    /**
     * @depends testRange
     */
    function testTooHighRange() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=100-200',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(416, $this->response->status);

    }

    /**
     * @depends testRange
     */
    function testCrazyRange() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=8-4',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(416, $this->response->status);

    }

    /**
     * @depends testRange
     */
    function testIfRangeEtag() {

        $node = $this->server->tree->getNodeForPath('test.txt');

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
            'HTTP_IF_RANGE'  => $node->getETag(),
        ];
        $filename = SABRE_TEMPDIR . '/test.txt';

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/13'],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(206, $this->response->status);
        $this->assertEquals('st c', stream_get_contents($this->response->body, 4));

    }

    /**
     * @depends testRange
     */
    function testIfRangeEtagIncorrect() {

        $node = $this->server->tree->getNodeForPath('test.txt');

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
            'HTTP_IF_RANGE'  => $node->getETag() . 'blabla',
        ];
        $filename = SABRE_TEMPDIR . '/test.txt';

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }

    /**
     * @depends testRange
     */
    function testIfRangeModificationDate() {

        $node = $this->server->tree->getNodeForPath('test.txt');

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
            'HTTP_IF_RANGE'  => 'tomorrow',
        ];
        $filename = SABRE_TEMPDIR . '/test.txt';

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/13'],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(206, $this->response->status);
        $this->assertEquals('st c', stream_get_contents($this->response->body, 4));

    }

    /**
     * @depends testRange
     */
    function testIfRangeModificationDateModified() {

        $node = $this->server->tree->getNodeForPath('test.txt');

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE'     => 'bytes=2-5',
            'HTTP_IF_RANGE'  => '-2 years',
        ];
        $filename = SABRE_TEMPDIR . '/test.txt';

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }
}
