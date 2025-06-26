<?php

declare(strict_types=1);

namespace Sabre\DAV;

use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\BadRequest;
use Sabre\HTTP;

class CorePluginTest extends \PHPUnit\Framework\TestCase
{
    public function testGetInfo()
    {
        $corePlugin = new CorePlugin();
        self::assertEquals('core', $corePlugin->getPluginInfo()['name']);
    }

    public function moveInvalidDepthHeaderProvider()
    {
        return [
            [0],
            [1],
        ];
    }

    /**
     * MOVE does only allow "infinity" every other header value is considered invalid.
     *
     * @dataProvider moveInvalidDepthHeaderProvider
     */
    public function testMoveWithInvalidDepth($depthHeader)
    {
        $request = new HTTP\Request('MOVE', '/path/');
        $response = new HTTP\Response();

        /** @var Server|MockObject */
        $server = $this->getMockBuilder(Server::class)->getMock();
        $corePlugin = new CorePlugin();
        $corePlugin->initialize($server);

        $server->expects($this->once())
            ->method('getCopyAndMoveInfo')
            ->willReturn(['depth' => $depthHeader]);

        $this->expectException(BadRequest::class);
        $corePlugin->httpMove($request, $response);
    }

    /**
     * MOVE does only allow "infinity" every other header value is considered invalid.
     */
    public function testMoveSupportsDepth()
    {
        $request = new HTTP\Request('MOVE', '/path/');
        $response = new HTTP\Response();

        /** @var Server|MockObject */
        $server = $this->getMockBuilder(Server::class)->getMock();
        $corePlugin = new CorePlugin();
        $corePlugin->initialize($server);

        $server->expects($this->once())
            ->method('getCopyAndMoveInfo')
            ->willReturn(['depth' => Server::DEPTH_INFINITY, 'destinationExists' => true, 'destination' => 'dst']);
        $corePlugin->httpMove($request, $response);
    }
}
