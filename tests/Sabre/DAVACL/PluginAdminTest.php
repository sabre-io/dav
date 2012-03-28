<?php

require_once 'Sabre/DAVACL/MockACLNode.php';
require_once 'Sabre/DAV/Auth/MockBackend.php';
require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';

class Sabre_DAVACL_PluginAdminTest extends PHPUnit_Framework_TestCase {

    function testNoAdminAccess() {

        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();

        $tree = array(
            new Sabre_DAVACL_MockACLNode('adminonly', array()),
            new Sabre_DAVACL_PrincipalCollection($principalBackend), 
        );

        $fakeServer = new Sabre_DAV_Server($tree);
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'realm');
        $fakeServer->addPlugin($plugin);
        $plugin = new Sabre_DAVACL_Plugin();
        $fakeServer->addPlugin($plugin);

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_DEPTH' => 1,
            'REQUEST_URI' => '/adminonly',
        ));

        $response = new Sabre_HTTP_ResponseMock();

        $fakeServer->httpRequest = $request;
        $fakeServer->httpResponse = $response;

        $fakeServer->exec();

        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status); 

    }

    /**
     * @depends testNoAdminAccess
     */
    function testAdminAccess() {

        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();

        $tree = array(
            new Sabre_DAVACL_MockACLNode('adminonly', array()),
            new Sabre_DAVACL_PrincipalCollection($principalBackend), 
        );

        $fakeServer = new Sabre_DAV_Server($tree);
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'realm');
        $fakeServer->addPlugin($plugin);
        $plugin = new Sabre_DAVACL_Plugin();
        $plugin->adminPrincipals = array(
            'principals/admin',
        );
        $fakeServer->addPlugin($plugin);

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_DEPTH' => 1,
            'REQUEST_URI' => '/adminonly',
        ));

        $response = new Sabre_HTTP_ResponseMock();

        $fakeServer->httpRequest = $request;
        $fakeServer->httpResponse = $response;

        $fakeServer->exec();

        $this->assertEquals('HTTP/1.1 200 OK', $response->status); 

    }
}
