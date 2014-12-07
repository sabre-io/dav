<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class AbstractBasicTest extends \PHPUnit_Framework_TestCase {

    function testCheckNoHeaders() {

        $request = new HTTP\Request();
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();

        $this->assertNull(
            $backend->check($request, $response)
        );

    }

    function testCheckUnknownUser() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'PHP_AUTH_USER' => 'username',
            'PHP_AUTH_PW' => 'wrongpassword',
        ));
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();

        $this->assertNull(
            $backend->check($request, $response)
        );

    }

    function testAuthenticate() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'PHP_AUTH_USER' => 'username',
            'PHP_AUTH_PW' => 'password',
        ));
        $response = new HTTP\Response();

        $backend = new AbstractBasicMock();
        $this->assertEquals(
            'principals/username',
            $backend->check($request, $response)
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
