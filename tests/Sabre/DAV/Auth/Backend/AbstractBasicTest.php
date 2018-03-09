<?php declare (strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV\Psr7RequestWrapper;
use Sabre\DAV\Psr7ResponseWrapper;
use Sabre\HTTP;

class AbstractBasicTest extends \PHPUnit_Framework_TestCase {

    function testCheckNoHeaders() {

        $request = new ServerRequest('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();

        $this->assertFalse(
            $backend->check(new Psr7RequestWrapper($request), $response)[0]
        );

    }

    function testCheckUnknownUser() {

        $request = new ServerRequest(
            'GET',
            '/',
            [
                'Authorization' => 'Basic ' . base64_encode('username:wrongpassword')
            ]
        );
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();

        $this->assertFalse(
            $backend->check(new Psr7RequestWrapper($request), $response)[0]
        );

    }

    function testCheckSuccess() {

        $request = new ServerRequest(
            'GET',
            '/',
            [
                'Authorization' => 'Basic ' . base64_encode('username:password')
            ]);
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();
        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check(new Psr7RequestWrapper($request), $response)
        );

    }

    function testRequireAuth() {

        $request = new ServerRequest('GET', '/');
        $response = new Psr7ResponseWrapper(function() { return new Response(); });

        $backend = new AbstractBasicMock();
        $backend->setRealm('writing unittests on a saturday night');
        $backend->challenge(new Psr7RequestWrapper($request), $response);

        $this->assertEquals(
            'Basic realm="writing unittests on a saturday night", charset="UTF-8"',
            $response->getResponse()->getHeaderLine('WWW-Authenticate')
        );

    }

}


class AbstractBasicMock extends AbstractBasic {

    /**
     * Validates a username and password
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    function validateUserPass($username, $password) {

        return ($username == 'username' && $password == 'password');

    }

}
