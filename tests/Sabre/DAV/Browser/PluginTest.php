<?php

declare(strict_types=1);

namespace Sabre\DAV\Browser;

use Sabre\DAV\Xml\Property\GetLastModified;
use DateTime;
use Sabre\DAV;
use Sabre\HTTP;

class PluginTest extends DAV\AbstractServerTestCase
{
    protected $plugin;

    public function setUp(): void
    {
        parent::setUp();
        $this->server->addPlugin($this->plugin = new Plugin());
        $this->server->tree->getNodeForPath('')->createDirectory('dir2');
    }

    public function testCollectionGet()
    {
        $request = new HTTP\Request('GET', '/dir');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(200, $this->response->getStatus(), 'Incorrect status received. Full response body: '.$this->response->getBodyAsString());
        self::assertEquals(
            [
                'X-Sabre-Version' => [DAV\Version::VERSION],
                'Content-Type' => ['text/html; charset=utf-8'],
                'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"],
            ],
            $this->response->getHeaders()
        );

        $body = $this->response->getBodyAsString();
        self::assertTrue(false !== strpos($body, '<title>dir'), $body);
        self::assertTrue(false !== strpos($body, '<a href="/dir/child.txt">'));
    }

    /**
     * Adding the If-None-Match should have 0 effect, but it threw an error.
     */
    public function testCollectionGetIfNoneMatch()
    {
        $request = new HTTP\Request('GET', '/dir');
        $request->setHeader('If-None-Match', '"foo-bar"');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(200, $this->response->getStatus(), 'Incorrect status received. Full response body: '.$this->response->getBodyAsString());
        self::assertEquals(
            [
                'X-Sabre-Version' => [DAV\Version::VERSION],
                'Content-Type' => ['text/html; charset=utf-8'],
                'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"],
            ],
            $this->response->getHeaders()
        );

        $body = $this->response->getBodyAsString();
        self::assertTrue(false !== strpos($body, '<title>dir'), $body);
        self::assertTrue(false !== strpos($body, '<a href="/dir/child.txt">'));
    }

    public function testCollectionGetRoot()
    {
        $request = new HTTP\Request('GET', '/');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        self::assertEquals(200, $this->response->status, 'Incorrect status received. Full response body: '.$this->response->getBodyAsString());
        self::assertEquals(
            [
                'X-Sabre-Version' => [DAV\Version::VERSION],
                'Content-Type' => ['text/html; charset=utf-8'],
                'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"],
            ],
            $this->response->getHeaders()
        );

        $body = $this->response->getBodyAsString();
        self::assertTrue(false !== strpos($body, '<title>/'), $body);
        self::assertTrue(false !== strpos($body, 'Nodes (3)'), $body);
        self::assertTrue(false !== strpos($body, '<a href="/dir/">'));
        self::assertTrue(false !== strpos($body, '<span class="btn disabled">'));
    }

    public function testGETPassthru()
    {
        $request = new HTTP\Request('GET', '/random');
        $response = new HTTP\Response();
        self::assertNull(
            $this->plugin->httpGet($request, $response)
        );
    }

    public function testPostOtherContentType()
    {
        $request = new HTTP\Request('POST', '/', ['Content-Type' => 'text/xml']);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(501, $this->response->status);
    }

    public function testPostNoContentType()
    {
        $request = new HTTP\Request('POST', '/', []);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(501, $this->response->status);
    }

    public function testPostNoSabreAction()
    {
        $request = new HTTP\Request('POST', '/', ['Content-Type' => 'application/x-www-form-urlencoded']);
        $request->setPostData([]);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(501, $this->response->status);
    }

    public function testPostMkCol()
    {
        $serverVars = [
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ];
        $postVars = [
            'sabreAction' => 'mkcol',
            'name' => 'new_collection',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setPostData($postVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(302, $this->response->status);
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Location' => ['/'],
        ], $this->response->getHeaders());

        self::assertTrue(is_dir(\Sabre\TestUtil::SABRE_TEMPDIR.'/new_collection'));
    }

    public function testGetAsset()
    {
        $request = new HTTP\Request('GET', '/?sabreAction=asset&assetName=favicon.ico');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(200, $this->response->getStatus(), 'Error: '.$this->response->getBodyAsString());
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['image/vnd.microsoft.icon'],
            'Content-Length' => ['4286'],
            'Cache-Control' => ['public, max-age=1209600'],
            'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"],
        ], $this->response->getHeaders());
    }

    public function testGetAsset404()
    {
        $request = new HTTP\Request('GET', '/?sabreAction=asset&assetName=flavicon.ico');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(404, $this->response->getStatus(), 'Error: '.$this->response->getBodyAsString());
    }

    public function testGetAssetEscapeBasePath()
    {
        $request = new HTTP\Request('GET', '/?sabreAction=asset&assetName=./../assets/favicon.ico');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(404, $this->response->getStatus(), 'Error: '.$this->response->getBodyAsString());
    }

    public function testCollectionNodesOrder()
    {
        $compareNodes = new \ReflectionMethod($this->plugin, 'compareNodes');
        $compareNodes->setAccessible(true);

        $day1 = new GetLastModified(new DateTime('2000-01-01'));
        $day2 = new GetLastModified(new DateTime('2000-01-02'));

        $file1 = [
            '{DAV:}getlastmodified' => $day1,
            'displayPath' => 'file1'
        ];
        $file1_clon = [
            '{DAV:}getlastmodified' => $day1,
            'displayPath' => 'file1'
        ];
        $file2 = [
            '{DAV:}getlastmodified' => $day1,
            'displayPath' => 'file2'
        ];
        $file2_newer = [
            '{DAV:}getlastmodified' => $day2,
            'displayPath' => 'file2'
        ];

        // Case 1: Newer node should come before older node
        self::assertEquals(-1, $compareNodes->invoke($this->plugin, $file2_newer, $file2));
        self::assertEquals(1, $compareNodes->invoke($this->plugin, $file1, $file2_newer));

        // Case 2: Nodes with same lastmodified but different displayPath (alphabetically)
        self::assertEquals(-1, $compareNodes->invoke($this->plugin, $file1_clon, $file2));
        self::assertEquals(1, $compareNodes->invoke($this->plugin, $file2, $file1));

        // Case 3: Nodes with same lastmodified and same displayPath
        self::assertEquals(0, $compareNodes->invoke($this->plugin, $file1, $file1_clon));
    }
}
