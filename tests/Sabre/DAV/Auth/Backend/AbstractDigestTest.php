<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;

class AbstractDigestTest extends \PHPUnit\Framework\TestCase
{
    public function testCheckNoHeaders()
    {
        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        self::assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckBadGetUserInfoResponse()
    {
        $header = 'username=null, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_DIGEST' => $header,
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        self::assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckBadGetUserInfoResponse2()
    {
        $this->expectException('Sabre\DAV\Exception');
        $header = 'username=array, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_DIGEST' => $header,
        ]);

        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        $backend->check($request, $response);
    }

    public function testCheckUnknownUser()
    {
        $header = 'username=false, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_DIGEST' => $header,
        ]);

        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        self::assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckBadPassword()
    {
        $header = 'username=user, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/',
            'PHP_AUTH_DIGEST' => $header,
        ]);

        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        self::assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheck()
    {
        $digestHash = md5('HELLO:12345:1:1:auth:'.md5('GET:/'));
        $header = 'username=user, realm=myRealm, nonce=12345, uri=/, response='.$digestHash.', opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_DIGEST' => $header,
        ]);

        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        self::assertEquals(
            [true, 'principals/user'],
            $backend->check($request, $response)
        );
    }

    public function testRequireAuth()
    {
        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $backend = new AbstractDigestMock();
        $backend->setRealm('writing unittests on a saturday night');
        $backend->challenge($request, $response);

        self::assertStringStartsWith(
            'Digest realm="writing unittests on a saturday night"',
            $response->getHeader('WWW-Authenticate')
        );
    }
}

class AbstractDigestMock extends AbstractDigest
{
    public function getDigestHash($realm, $userName)
    {
        switch ($userName) {
            case 'null': return null;
            case 'false': return false;
            case 'array': return [];
            case 'user': return 'HELLO';
        }
    }
}
