<?php declare (strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV\Psr7RequestWrapper;
use Sabre\DAV\Psr7ResponseWrapper;
use Sabre\HTTP;

class AbstractDigestTest extends \PHPUnit_Framework_TestCase {

    function testCheckNoHeaders() {

        $request = new ServerRequest('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        $this->assertFalse(
            $backend->check(new Psr7RequestWrapper($request), $response)[0]
        );

    }

    function testCheckBadGetUserInfoResponse() {

        $header = 'username=null, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = new ServerRequest('GET',
            '/', [
            'Auth-Digest' => $header,
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        $this->assertFalse(
            $backend->check(new Psr7RequestWrapper($request), $response)[0]
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception
     */
    function testCheckBadGetUserInfoResponse2() {

        $header = 'username=array, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = new ServerRequest('GET', '/', [
            'Authorization' => 'Digest ' . $header,
        ]);

        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        $backend->check(new Psr7RequestWrapper($request), $response);

    }

    function testCheckUnknownUser() {

        $header = 'username=false, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = new ServerRequest('GET', '/', [
            'Auth-Digest' => $header,
        ]);

        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        $this->assertFalse(
            $backend->check(new Psr7RequestWrapper($request), $response)[0]
        );

    }

    function testCheckBadPassword() {

        $header = 'username=user, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = new ServerRequest(
            'PUT',
            '/', [
            'Auth-Digest' => $header,
        ]);

        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        $this->assertFalse(
            $backend->check(new Psr7RequestWrapper($request), $response)[0]
        );

    }

    function testCheck() {

        $digestHash = md5('HELLO:12345:1:1:auth:' . md5('GET:/'));
        $header = 'username=user, realm=myRealm, nonce=12345, uri=/, response=' . $digestHash . ', opaque=1, qop=auth, nc=1, cnonce=1';
        $request = new ServerRequest('GET', '/', [
            'Authorization' => 'Digest ' . $header,
        ]);

        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        $this->assertEquals(
            [true, 'principals/user'],
            $backend->check(new Psr7RequestWrapper($request), $response)
        );

    }

    function testRequireAuth() {

        $request = new ServerRequest('GET', '/');
        $response = new Psr7ResponseWrapper(function() { return new Response(); });

        $backend = new AbstractDigestMock();
        $backend->setRealm('writing unittests on a saturday night');
        $backend->challenge(new Psr7RequestWrapper($request), $response);

        $this->assertStringStartsWith(
            'Digest realm="writing unittests on a saturday night"',
            $response->getResponse()->getHeaderLine('WWW-Authenticate')
        );

    }

}


class AbstractDigestMock extends AbstractDigest {

    function getDigestHash($realm, $userName) {

        switch ($userName) {
            case 'null' : return null;
            case 'false' : return false;
            case 'array' : return [];
            case 'user'  : return 'HELLO';
        }

    }

}
