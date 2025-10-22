<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Generator;
use PHPUnit\Framework\TestCase;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class CorePluginTest extends TestCase
{
    private CorePlugin $plugin;

    public function testGetInfo()
    {
        self::assertEquals('core', $this->plugin->getPluginInfo()['name']);
    }

    /**
     * @dataProvider beforePropertyResolutionEventData
     */
    public function testBeforePropertyResolutionEvent(string $basePath, array $originalPropFinds, array $modifiedPropFinds): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getPath')->willReturn($basePath);
        $request->method('getBodyAsString')->willReturn('');

        $response = $this->createMock(ResponseInterface::class);

        $server = $this->getMockBuilder(Server::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getHTTPDepth',
                'getPlugins',
                'getHTTPPrefer',
                'generatePropFindsForPath',
                'getNodePropertiesGenerator',
                'generateMultiStatus',
                'emit',
            ])
            ->getMock();

        $server->method('getHTTPDepth')
            ->willReturn(1);
        $server->method('getPlugins')
            ->willReturn([]);
        $server->method('getHTTPPrefer')
            ->willReturn(['return' => null]);

        $server->expects($this->once())
            ->method('generatePropFindsForPath')
            ->willReturn($originalPropFinds);

        $server->method('generateMultiStatus')
            ->willReturn('');

        $server->method('emit')
            ->willReturnCallback(
                function (string $eventName, $eventArgs) use ($originalPropFinds, $modifiedPropFinds) {
                    $this->assertEquals('beforePropertyResolution', $eventName);
                    /** @var iterable $propFinds */
                    [$propFinds] = $eventArgs;
                    $this->assertIsIterable($propFinds);
                    $this->assertEquals($originalPropFinds, $propFinds);
                    $eventArgs[0] = $this->toGenerator($modifiedPropFinds);

                    return true;
                }
            );

        $server->method('getNodePropertiesGenerator')
            ->willReturnCallback(function ($propFinds) use ($modifiedPropFinds) {
                // check that the generator received the modified list of PropFinds
                $this->assertIsIterable($propFinds);
                $array = iterator_to_array($propFinds);
                $this->assertEquals($modifiedPropFinds, $array);

                return $this->toGenerator($modifiedPropFinds);
            });

        $server->method('generateMultiStatus')
            ->willReturnCallback(function ($fileProperties, $minimal) use ($modifiedPropFinds) {
                // check that generateMultiStatus receives the modified list of PropFinds
                $array = iterator_to_array($fileProperties);
                $this->assertEquals($modifiedPropFinds, $array);
            });

        $this->plugin->initialize($server);
        $this->plugin->httpPropFind($request, $response);
    }

    private function toGenerator(array $paths): Generator
    {
        yield from $paths;
    }

    /**
     * @param string[] $paths
     *
     * @return array<array{PropFind, INode}>
     */
    private function getPropFindNodeTuples(array $paths): array
    {
        $propFindNodeTuples = [];
        foreach ($paths as $path) {
            $propFindNodeTuples[] = [
                new PropFind($path, [], 1, PropFind::ALLPROPS),
                $this->createMock(INode::class),
            ];
        }

        return $propFindNodeTuples;
    }

    public function beforePropertyResolutionEventData(): array
    {
        $basePath = 'files/user';
        $paths = [$basePath];
        for ($i = 0; $i < 5; ++$i) {
            $paths[] = "$basePath/$i";
        }

        $originalPropFinds = $this->getPropFindNodeTuples($paths);
        $modifiedPropFinds = [$originalPropFinds[0]];

        return [
            'Test PROPFIND with all PropFind objects' => [
                $basePath, $originalPropFinds, $originalPropFinds,
            ],
            'Test PROPFIND with removed PropFind objects' => [
                $basePath, $originalPropFinds, $modifiedPropFinds,
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = new CorePlugin();
    }
}
