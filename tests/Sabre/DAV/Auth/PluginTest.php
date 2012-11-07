<?php

namespace Sabre\DAV\Auth;

use Sabre\HTTP;
use Sabre\DAV;

require_once 'Sabre/HTTP/ResponseMock.php';

class PluginTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $this->assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('auth'));

    }

    /**
     * @depends testInit
     */
    function testAuthenticate() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $fakeServer->addPlugin($plugin);
        $fakeServer->broadCastEvent('beforeMethod',array('GET','/'));

    }



    /**
     * @depends testInit
     * @expectedException Sabre\DAV\Exception\NotAuthenticated
     */
    function testAuthenticateFail() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'failme');
        $fakeServer->addPlugin($plugin);
        $fakeServer->broadCastEvent('beforeMethod',array('GET','/'));

    }

    function testReportPassThrough() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $fakeServer->addPlugin($plugin);

        $request = new HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_CONTENT_TYPE' => 'application/xml',
            'REQUEST_URI' => '/',
        ));
        $request->setBody('<?xml version="1.0"?><s:somereport xmlns:s="http://www.rooftopsolutions.nl/NS/example" />');

        $fakeServer->httpRequest = $request;
        $fakeServer->httpResponse = new HTTP\ResponseMock();
        $fakeServer->exec();

        $this->assertEquals('HTTP/1.1 403 Forbidden', $fakeServer->httpResponse->status);

    }

    /**
     * @depends testInit
     */
    function testGetCurrentUserPrincipal() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $fakeServer->addPlugin($plugin);
        $fakeServer->broadCastEvent('beforeMethod',array('GET','/'));
        $this->assertEquals('admin', $plugin->getCurrentUser());

    }

}

