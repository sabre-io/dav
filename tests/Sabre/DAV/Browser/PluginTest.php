<?php declare (strict_types=1);

namespace Sabre\DAV\Browser;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer{

    protected $plugin;

    function setUp() {

        parent::setUp();
        $this->server->addPlugin($this->plugin = new Plugin());
        $this->server->tree->getNodeForPath('')->createDirectory('dir2');

    }

    function testCollectionGet() {

        $request = new ServerRequest('GET', '/dir');


        $response = $this->server->handle($request);
        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), "Incorrect status received. Full response body: " . $body);
        $this->assertEquals(
            [
                'Content-Type'            => ['text/html; charset=utf-8'],
                'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"]
            ],
            $response->getHeaders()
        );


        $this->assertTrue(strpos($body, '<title>dir') !== false, $body);
        $this->assertTrue(strpos($body, '<a href="/dir/child.txt">') !== false);

    }

    /**
     * Adding the If-None-Match should have 0 effect, but it threw an error.
     */
    function testCollectionGetIfNoneMatch() {

        $request = new ServerRequest('GET', '/dir', [
            'If-None-Match', '"foo-bar"'
        ]);

        $response = $this->server->handle($request);
        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), "Incorrect status received. Full response body: " . $body);
        $this->assertEquals(
            [
                'Content-Type'            => ['text/html; charset=utf-8'],
                'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"]
            ],
            $response->getHeaders()
        );


        $this->assertTrue(strpos($body, '<title>dir') !== false, $body);
        $this->assertTrue(strpos($body, '<a href="/dir/child.txt">') !== false);

    }
    function testCollectionGetRoot() {

        $request = new ServerRequest('GET', '/');
        $response = $this->server->handle($request);

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), "Incorrect status received. Full response body: " . $body);
        $this->assertEquals(
            [
                'Content-Type'            => ['text/html; charset=utf-8'],
                'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"]
            ],
            $response->getHeaders()
        );


        $this->assertTrue(strpos($body, '<title>/') !== false, $body);
        $this->assertTrue(strpos($body, '<a href="/dir/">') !== false);
        $this->assertTrue(strpos($body, '<span class="btn disabled">') !== false);

    }

    function testGETPassthru() {

        $request = new ServerRequest('GET', '/random');
        $response = new HTTP\Response();
        $this->assertNull(
            $this->plugin->httpGet(new DAV\Psr7RequestWrapper($request), $response)
        );

    }

    function testPostOtherContentType() {

        $request = new ServerRequest('POST', '/', ['Content-Type' => 'text/xml']);
        $response = $this->server->handle($request);


        $this->assertEquals(501, $response->getStatusCode());

    }

    function testPostNoSabreAction() {

        $request = (new ServerRequest('POST', '/', ['Content-Type' => 'application/x-www-form-urlencoded']))
            ->withParsedBody([]);
        $response = $this->server->handle($request, 501);
        $this->assertEquals(501, $response->getStatusCode(), $response->getBody()->getContents());

    }

    function testPostMkCol() {

        $postVars = [
            'sabreAction' => 'mkcol',
            'name'        => 'new_collection',
        ];

        $request = (new ServerRequest('POST', '/', [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]))->withParsedBody($postVars);

        $response = $this->server->handle($request);

        $this->assertEquals(302, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals([

            'Location'        => ['/'],
        ], $response->getHeaders());

        $this->assertTrue(is_dir(SABRE_TEMPDIR . '/new_collection'));

    }

    function testGetAsset() {

        $request = (new ServerRequest('GET', '/?sabreAction=asset&assetName=favicon.ico'))
            ->withQueryParams([
                'sabreAction' => 'asset',
                'assetName' => 'favicon.ico'
            ]);

        $response = $this->server->handle($request);

        $this->assertEquals(200, $response->getStatusCode(), 'Error: ' . $response->getBody()->getContents());
        $this->assertEquals([
            'Content-Type'            => ['image/vnd.microsoft.icon'],
            'Content-Length'          => ['4286'],
            'Cache-Control'           => ['public, max-age=1209600'],
            'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"]
        ], $response->getHeaders());

    }

    function testGetAsset404() {

        $request = (new ServerRequest('GET', '/?sabreAction=asset&assetName=flavicon.ico'))
            ->withQueryParams([
                'sabreAction' => 'asset',
                'assetName' => 'flavicon.ico'
            ]);
        $response = $this->server->handle($request);


        $this->assertEquals(404, $response->getStatusCode(), 'Error: ' . $response->getBody()->getContents());

    }

    function testGetAssetEscapeBasePath() {

        $request = (new ServerRequest('GET', '/?sabreAction=asset&assetName=./../assets/favicon.ico'))
            ->withQueryParams([
                'sabreAction' => 'asset',
                'assetName' => './../assets/favicon.ico'
            ]);

        $response = $this->server->handle($request);


        $this->assertEquals(404, $response->getStatusCode(), 'Error: ' . $response->getBody()->getContents());

    }

    public function testGetPlugins()
    {
        $request = (new ServerRequest('GET', '/'))
            ->withQueryParams([
                'sabreAction' => 'plugins'
            ]);
        $response = $this->server->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $responseBody = $response->getBody()->getContents();
        /** @var  $plugin DAV\ServerPlugin */
        foreach($this->server->getPlugins() as $plugin) {
            $this->assertContains('<th>' . $plugin->getPluginName() . '</th>', $responseBody);
        }
    }
}
