<?php declare (strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;

class ApacheTest extends \PHPUnit\Framework\TestCase {

    function testConstruct() {

        $backend = new Apache();
        $this->assertInstanceOf('Sabre\DAV\Auth\Backend\Apache', $backend);

    }

    function testNoHeader() {

        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();
        $backend = new Apache();

        $this->assertFalse(
            $backend->check($request, $response)[0]
        );

    }

    function testRemoteUser() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/',
            'REMOTE_USER'    => 'username',
        ]);
        $response = new HTTP\Response();
        $backend = new Apache();

        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check($request, $response)
        );

    }

    function testRedirectRemoteUser() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD'       => 'GET',
            'REQUEST_URI'          => '/',
            'REDIRECT_REMOTE_USER' => 'username',
        ]);
        $response = new HTTP\Response();
        $backend = new Apache();

        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check($request, $response)
        );

    }

    function testRequireAuth() {

        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $backend = new Apache();
        $backend->challenge($request, $response);

        $this->assertNull(
            $response->getHeader('WWW-Authenticate')
        );

    }
}
