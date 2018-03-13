<?php declare (strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;

class AbstractBearerTest extends \PHPUnit\Framework\TestCase {

    function testCheckNoHeaders() {

        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();

        $this->assertFalse(
            $backend->check($request, $response)[0]
        );

    }

    function testCheckInvalidToken() {

        $request = new HTTP\Request('GET', '/', [
            'Authorization' => 'Bearer foo',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();

        $this->assertFalse(
            $backend->check($request, $response)[0]
        );

    }

    function testCheckSuccess() {

        $request = new HTTP\Request('GET', '/', [
            'Authorization' => 'Bearer valid',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();
        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check($request, $response)
        );

    }

    function testRequireAuth() {

        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();
        $backend->setRealm('writing unittests on a saturday night');
        $backend->challenge($request, $response);

        $this->assertEquals(
            'Bearer realm="writing unittests on a saturday night"',
            $response->getHeader('WWW-Authenticate')
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
