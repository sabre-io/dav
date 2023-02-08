<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;

class AbstractBearerTest extends \PHPUnit\Framework\TestCase
{
    public function testCheckNoHeaders()
    {
        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();

        self::assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckInvalidToken()
    {
        $request = new HTTP\Request('GET', '/', [
            'Authorization' => 'Bearer foo',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();

        self::assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckSuccess()
    {
        $request = new HTTP\Request('GET', '/', [
            'Authorization' => 'Bearer valid',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();
        self::assertEquals(
            [true, 'principals/username'],
            $backend->check($request, $response)
        );
    }

    public function testRequireAuth()
    {
        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();
        $backend->setRealm('writing unittests on a saturday night');
        $backend->challenge($request, $response);

        self::assertEquals(
            'Bearer realm="writing unittests on a saturday night"',
            $response->getHeader('WWW-Authenticate')
        );
    }
}

class AbstractBearerMock extends AbstractBearer
{
    /**
     * Validates a bearer token.
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $bearerToken
     *
     * @return bool
     */
    public function validateBearerToken($bearerToken)
    {
        return 'valid' === $bearerToken ? 'principals/username' : false;
    }
}
