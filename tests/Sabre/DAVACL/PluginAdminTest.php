<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class PluginAdminTest extends \PHPUnit\Framework\TestCase
{
    public $server;

    public function setUp()
    {
        $principalBackend = new PrincipalBackend\Mock();

        $tree = [
            new MockACLNode('adminonly', []),
            new PrincipalCollection($principalBackend),
        ];

        $this->server = new DAV\Server($tree);
        $this->server->sapi = new HTTP\SapiMock();
        $plugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock());
        $this->server->addPlugin($plugin);
    }

    public function testNoAdminAccess()
    {
        $plugin = new Plugin();
        $this->server->addPlugin($plugin);

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_DEPTH' => 1,
            'REQUEST_URI' => '/adminonly',
        ]);

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $this->assertEquals(403, $response->status);
    }

    /**
     * @depends testNoAdminAccess
     */
    public function testAdminAccess()
    {
        $plugin = new Plugin();
        $plugin->adminPrincipals = [
            'principals/admin',
        ];
        $this->server->addPlugin($plugin);

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_DEPTH' => 1,
            'REQUEST_URI' => '/adminonly',
        ]);

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $this->assertEquals(200, $response->status);
    }
}
