<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;


require_once 'Sabre/DAVACL/MockACLNode.php';
require_once 'Sabre/DAV/Auth/MockBackend.php';
require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';

class PluginAdminTest extends \PHPUnit_Framework_TestCase {

    function testNoAdminAccess() {

        $principalBackend = new MockPrincipalBackend();

        $tree = array(
            new MockACLNode('adminonly', array()),
            new PrincipalCollection($principalBackend),
        );

        $fakeServer = new DAV\Server($tree);
        $plugin = new DAV\Auth\Plugin(new DAV\Auth\MockBackend(),'realm');
        $fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $fakeServer->addPlugin($plugin);

        $request = new HTTP\Request(array(
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_DEPTH' => 1,
            'REQUEST_URI' => '/adminonly',
        ));

        $response = new HTTP\ResponseMock();

        $fakeServer->httpRequest = $request;
        $fakeServer->httpResponse = $response;

        $fakeServer->exec();

        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status);

    }

    /**
     * @depends testNoAdminAccess
     */
    function testAdminAccess() {

        $principalBackend = new MockPrincipalBackend();

        $tree = array(
            new MockACLNode('adminonly', array()),
            new PrincipalCollection($principalBackend),
        );

        $fakeServer = new DAV\Server($tree);
        $plugin = new DAV\Auth\Plugin(new DAV\Auth\MockBackend(),'realm');
        $fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->adminPrincipals = array(
            'principals/admin',
        );
        $fakeServer->addPlugin($plugin);

        $request = new HTTP\Request(array(
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_DEPTH' => 1,
            'REQUEST_URI' => '/adminonly',
        ));

        $response = new HTTP\ResponseMock();

        $fakeServer->httpRequest = $request;
        $fakeServer->httpResponse = $response;

        $fakeServer->exec();

        $this->assertEquals('HTTP/1.1 200 OK', $response->status);

    }
}
