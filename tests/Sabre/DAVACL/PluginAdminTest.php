<?php declare (strict_types=1);

namespace Sabre\DAVACL;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAVACL/MockACLNode.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class PluginAdminTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DAV\Server
     */
    public $server;

    function setUp() {

        $principalBackend = new PrincipalBackend\Mock();

        $tree = [
            new MockACLNode('adminonly', []),
            new PrincipalCollection($principalBackend),
        ];

        $this->server = new DAV\Server($tree, null, null, function(){});

        $plugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock());
        $this->server->addPlugin($plugin);
    }

    function testNoAdminAccess() {

        $plugin = new Plugin();
        $this->server->addPlugin($plugin);

        $request = new ServerRequest('OPTIONS', '/adminonly', ['Depth' => '1']);


        $response = $this->server->handle($request);



        $this->assertEquals(403, $response->getStatusCode());

    }

    /**
     * @depends testNoAdminAccess
     */
    function testAdminAccess() {

        $plugin = new Plugin();
        $plugin->adminPrincipals = [
            'principals/admin',
        ];
        $this->server->addPlugin($plugin);

        $request = new ServerRequest('OPTIONS', '/adminonly', ['Depth'     => 1]);

        $response = $this->server->handle($request);


        $this->assertEquals(200, $response->getStatusCode());

    }
}
