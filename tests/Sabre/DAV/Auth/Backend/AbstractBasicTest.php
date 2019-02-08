<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;

class AbstractBasicTest extends \PHPUnit\Framework\TestCase
{
    public function testCheckNoHeaders()
    {
        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();

        $this->assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckUnknownUser()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_USER' => 'username',
            'PHP_AUTH_PW' => 'wrongpassword',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();

        $this->assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckSuccess()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_USER' => 'username',
            'PHP_AUTH_PW' => 'password',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();
        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check($request, $response)
        );
    }

    public function testRequireAuth()
    {
        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();
        $backend->setRealm('writing unittests on a saturday night');
        $backend->challenge($request, $response);

        $this->assertEquals(
            'Basic realm="writing unittests on a saturday night", charset="UTF-8"',
            $response->getHeader('WWW-Authenticate')
        );
    }
}

class AbstractBasicMock extends AbstractBasic
{
    /**
     * Validates a username and password.
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function validateUserPass($username, $password)
    {
        return 'username' == $username && 'password' == $password;
    }
}
