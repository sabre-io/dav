<?php declare (strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV\Psr7RequestWrapper;
use Sabre\DAV\Psr7ResponseWrapper;
use Sabre\HTTP;

class ApacheTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $backend = new Apache();
        $this->assertInstanceOf('Sabre\DAV\Auth\Backend\Apache', $backend);

    }

    function testNoHeader() {

        $request = new ServerRequest('GET', '/');
        $response = new HTTP\Response();
        $backend = new Apache();

        $this->assertFalse(
            $backend->check(new Psr7RequestWrapper($request), $response)[0]
        );

    }

    function testRemoteUser() {

        $request = new ServerRequest('GET',
            '/',
            [],
            null,
            '1.1',
            ['REMOTE_USER'    => 'username',
        ]);
        $backend = new Apache();

        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check(new Psr7RequestWrapper($request), new Psr7ResponseWrapper(function() { return new Response(500); }))
        );

    }

    function testRedirectRemoteUser() {

        $request = new ServerRequest(
            'GET',
            '/',
            [],
            null,
            '1.1',
            ['REDIRECT_REMOTE_USER' => 'username',
        ]);
        $response = new HTTP\Response();
        $backend = new Apache();

        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check(new Psr7RequestWrapper($request), $response)
        );

    }

    function testRequireAuth() {

        $request = new ServerRequest('GET', '/');
        $response = new Psr7ResponseWrapper(function() { return new Response(); });

        $backend = new Apache();
        $backend->challenge(new Psr7RequestWrapper($request), $response);

        $this->assertEmpty($response->getResponse()->getHeader('WWW-Authenticate'));

    }
}
