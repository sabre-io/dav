<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

class ServerPluginTest extends AbstractServer
{
    /**
     * @var Sabre\DAV\TestPlugin
     */
    protected $testPlugin;

    public function setup(): void
    {
        parent::setUp();

        $testPlugin = new TestPlugin();
        $this->server->addPlugin($testPlugin);
        $this->testPlugin = $testPlugin;
    }

    public function testBaseClass()
    {
        $p = new ServerPluginMock();
        self::assertEquals([], $p->getFeatures());
        self::assertEquals([], $p->getHTTPMethods(''));
        self::assertEquals(
            [
                'name' => \Sabre\DAV\ServerPluginMock::class,
                'description' => null,
                'link' => null,
            ], $p->getPluginInfo()
        );
    }

    public function testOptions()
    {
        $serverVars = [
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'OPTIONS',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        self::assertEquals([
            'DAV' => ['1, 3, extended-mkcol, drinking'],
            'MS-Author-Via' => ['DAV'],
            'Allow' => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT, BEER, WINE'],
            'Accept-Ranges' => ['bytes'],
            'Content-Length' => ['0'],
            'X-Sabre-Version' => [Version::VERSION],
        ], $this->response->getHeaders());

        self::assertEquals(200, $this->response->status);
        self::assertEquals('', $this->response->getBodyAsString());
        self::assertEquals('OPTIONS', $this->testPlugin->beforeMethod);
    }

    public function testGetPlugin()
    {
        self::assertEquals($this->testPlugin, $this->server->getPlugin(get_class($this->testPlugin)));
    }

    public function testUnknownPlugin()
    {
        self::assertNull($this->server->getPlugin('SomeRandomClassName'));
    }

    public function testGetSupportedReportSet()
    {
        self::assertEquals([], $this->testPlugin->getSupportedReportSet('/'));
    }

    public function testGetPlugins()
    {
        self::assertEquals(
            [
                get_class($this->testPlugin) => $this->testPlugin,
                'core' => $this->server->getPlugin('core'),
            ],
            $this->server->getPlugins()
        );
    }
}

class ServerPluginMock extends ServerPlugin
{
    public function initialize(Server $s)
    {
    }
}
