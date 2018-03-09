<?php declare (strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV\Psr7RequestWrapper;
use Sabre\DAV\Psr7ResponseWrapper;
use Sabre\HTTP;

class AbstractBearerTest extends \PHPUnit_Framework_TestCase {

    function testCheckNoHeaders() {

        $request = new ServerRequest('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();

        $this->assertFalse(
            $backend->check(new Psr7RequestWrapper($request), $response)[0]
        );

    }

    function testCheckInvalidToken() {

        $request = new ServerRequest('GET', '/', [
            'Authorization' => 'Bearer foo',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();

        $this->assertFalse(
            $backend->check(new Psr7RequestWrapper($request), $response)[0]
        );

    }

    function testCheckSuccess() {

        $request = new ServerRequest('GET', '/', [
            'Authorization' => 'Bearer valid',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();
        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check(new Psr7RequestWrapper($request), $response)
        );

    }

    function testRequireAuth() {

        $request = new ServerRequest('GET', '/');
        $response = new Psr7ResponseWrapper(function() { return new Response(); });

        $backend = new AbstractBearerMock();
        $backend->setRealm('writing unittests on a saturday night');
        $backend->challenge(new Psr7RequestWrapper($request), $response);

        $this->assertEquals(
            'Bearer realm="writing unittests on a saturday night"',
            $response->getResponse()->getHeaderLine('WWW-Authenticate')
        );

    }

}


class AbstractBearerMock extends AbstractBearer {

    /**
     * Validates a bearer token
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $bearerToken
     * @return bool
     */
    function validateBearerToken($bearerToken) {

        return 'valid' === $bearerToken ? 'principals/username' : false;

    }

}
