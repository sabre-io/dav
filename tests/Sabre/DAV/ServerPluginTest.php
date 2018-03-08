<?php declare (strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';
require_once 'Sabre/DAV/TestPlugin.php';

class ServerPluginTest extends AbstractServer {

    /**
     * @var Sabre\DAV\TestPlugin
     */
    protected $testPlugin;

    function setUp() {

        parent::setUp();

        $testPlugin = new TestPlugin();
        $this->server->addPlugin($testPlugin);
        $this->testPlugin = $testPlugin;

    }

    /**
     */
    function testBaseClass() {

        $p = new ServerPluginMock();
        $this->assertEquals([], $p->getFeatures());
        $this->assertEquals([], $p->getHTTPMethods(''));
        $this->assertEquals(
            [
                'name'        => 'Sabre\DAV\ServerPluginMock',
                'description' => null,
                'link'        => null
            ], $p->getPluginInfo()
        );

    }

    function testOptions() {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'OPTIONS',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->start();

        $this->assertEquals([
            'DAV'             => ['1, 3, extended-mkcol, drinking'],
            'MS-Author-Via'   => ['DAV'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT, BEER, WINE'],
            'Accept-Ranges'   => ['bytes'],
            'Content-Length'  => ['0'],
            'X-Sabre-Version' => [Version::VERSION],
        ], $this->getResponse()->getHeaders());

        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $this->assertEquals('', $this->getResponse()->getBody()->getContents());
        $this->assertEquals('OPTIONS', $this->testPlugin->beforeMethod);


    }

    function testGetPlugin() {

        $this->assertEquals($this->testPlugin, $this->server->getPlugin(get_class($this->testPlugin)));

    }

    function testUnknownPlugin() {

        $this->assertNull($this->server->getPlugin('SomeRandomClassName'));

    }

    function testGetSupportedReportSet() {

        $this->assertEquals([], $this->testPlugin->getSupportedReportSet('/'));

    }

    function testGetPlugins() {

        $this->assertEquals(
            [
                get_class($this->testPlugin) => $this->testPlugin,
                'core'                       => $this->server->getPlugin('core'),
            ],
            $this->server->getPlugins()
        );

    }


}

class ServerPluginMock extends ServerPlugin {

    function initialize(Server $s) { }

}
