<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\DAV\Exception\BadRequest;
use Sabre\HTTP;

class ServerCopyMoveTest extends AbstractServerTestCase
{
    public function testMissingDestinationHeader()
    {
        $request = new HTTP\Request('COPY', '/', ['Depth' => 'infinity']);

        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('The destination header was not supplied');
        $this->server->getCopyAndMoveInfo($request);
    }

    public function testMissingDepthHeader()
    {
        $request = new HTTP\Request('COPY', '/', ['Destination' => '/destination']);

        $this->assertEquals(Server::DEPTH_INFINITY, $this->server->getCopyAndMoveInfo($request)['depth']);
    }

    /**
     * Only 'infinity' and positive (incl. 0) numbers are allowed.
     *
     * @dataProvider dataInvalidDepthHeader
     */
    public function testInvalidDepthHeader(string $headerValue)
    {
        $request = new HTTP\Request('COPY', '/', ['Destination' => '/destination', 'Depth' => $headerValue]);

        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('The HTTP Depth header may only be "infinity", 0 or a positive integer');
        $this->server->getCopyAndMoveInfo($request);
    }

    public function dataInvalidDepthHeader()
    {
        return [
            ['-1'],
            ['0.5'],
            ['2f'],
            ['inf'],
        ];
    }

    /**
     * Only 'infinity' and positive (incl. 0) numbers are allowed.
     *
     * @dataProvider dataDepthHeader
     *
     * @param string|int $expectedDepth
     */
    public function testValidDepthHeader(array $depthHeader, $expectedDepth)
    {
        $request = new HTTP\Request('COPY', '/', array_merge(['Destination' => '/dst'], $depthHeader));

        $this->assertEquals($expectedDepth, $this->server->getCopyAndMoveInfo($request)['depth']);
    }

    public function dataDepthHeader()
    {
        return [
            [
                [],
                Server::DEPTH_INFINITY,
            ],
            [
                ['Depth' => 'infinity'],
                Server::DEPTH_INFINITY,
            ],
            [
                ['Depth' => 'INFINITY'],
                Server::DEPTH_INFINITY,
            ],
            [
                ['Depth' => '0'],
                0,
            ],
            [
                ['Depth' => '10'],
                10,
            ],
        ];
    }
}
