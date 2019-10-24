<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';
require_once 'Sabre/DAV/TestPlugin.php';

class ServerPluginTest extends AbstractServer
{
    /**
     * @var Sabre\DAV\TestPlugin
     */
    protected $testPlugin;

    public function setUp()
    {
        parent::setUp();

        $testPlugin = new TestPlugin();
        $this->server->addPlugin($testPlugin);
        $this->testPlugin = $testPlugin;
    }

    public function testBaseClass()
    {
        $p = new ServerPluginMock();
        $this->assertEquals([], $p->getFeatures());
        $this->assertEquals([], $p->getHTTPMethods(''));
        $this->assertEquals(
            [
                'name' => 'Sabre\DAV\ServerPluginMock',
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

        $this->assertEquals([
            'DAV' => ['1, 3, extended-mkcol, drinking'],
            'MS-Author-Via' => ['DAV'],
            'Allow' => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT, BEER, WINE'],
            'Accept-Ranges' => ['bytes'],
            'Content-Length' => ['0'],
            'X-Sabre-Version' => [Version::VERSION],
        ], $this->response->getHeaders());

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('', $this->response->getBodyAsString());
        $this->assertEquals('OPTIONS', $this->testPlugin->beforeMethod);
    }

    public function testGetPlugin()
    {
        $this->assertEquals($this->testPlugin, $this->server->getPlugin(get_class($this->testPlugin)));
    }

    public function testUnknownPlugin()
    {
        $this->assertNull($this->server->getPlugin('SomeRandomClassName'));
    }

    public function testGetSupportedReportSet()
    {
        $this->assertEquals([], $this->testPlugin->getSupportedReportSet('/'));
    }

    public function testGetPlugins()
    {
        $this->assertEquals(
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
