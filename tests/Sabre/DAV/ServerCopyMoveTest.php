<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\DAV\Exception\BadRequest;
use Sabre\HTTP;

class ServerCopyMoveTest extends AbstractServerTestCase
{
    /**
     * Only 'infinity' and positive (incl. 0) numbers are allowed.
     *
     * @dataProvider dataInvalidDepthHeader
     */
    public function testInvalidDepthHeader(?string $headerValue)
    {
        $request = new HTTP\Request('COPY', '/', null !== $headerValue ? ['Depth' => $headerValue] : []);

        $this->expectException(BadRequest::class);
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
                'infinity',
            ],
            [
                ['Depth' => 'infinity'],
                'infinity',
            ],
            [
                ['Depth' => 'INFINITY'],
                'infinity',
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
