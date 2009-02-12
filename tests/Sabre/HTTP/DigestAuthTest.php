<?php

require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_HTTP_DigestAuthTest extends PHPUnit_Framework_TestCase {

    private $response;
    private $request;
    private $basicAuth;

    const REALM = 'SabreDAV unittest';

    public function setUp() {

        $this->response = new Sabre_HTTP_ResponseMock();
        $this->auth = new Sabre_HTTP_DigestAuth();
        $this->auth->setRealm(self::REALM);
        $this->auth->setHTTPResponse($this->response);

    }

    public function testDigest() {
        
        list($nonce,$opaque) = $this->getServerTokens();

        $username = 'admin';
        $password = 12345;
        $nc = '00002';
        $cnonce = uniqid();

        $digestHash = md5(
            md5($username . ':' . self::REALM . ':' . $password) . ':' .
            $nonce . ':' .
            $nc . ':' .
            $cnonce . ':' .
            'auth:' .
            md5('GET' . ':' . '/')
        );

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'GET',
            'PHP_AUTH_DIGEST' => 'username="'.$username.'", realm="' . self::REALM . '", nonce="' . $nonce . '", uri="/", response="' . $digestHash . '", opaque="' . $opaque . '", qop=auth,nc='.$nc.',cnonce="' . $cnonce . '"',
        ));
        
        $this->auth->setHTTPRequest($request);
        $this->auth->init();
        
        $this->assertTrue($this->auth->validateA1(md5($username . ':' . self::REALM . ':' . $password)),'Authentication is deemed invalid through validateA1');
        $this->assertTrue($this->auth->validatePassword($password),'Authentication is deemed invalid through validatePassword');

    }

    public function testDigestCGIFormat() {
        
        list($nonce,$opaque) = $this->getServerTokens();

        $username = 'admin';
        $password = 12345;
        $nc = '00002';
        $cnonce = uniqid();

        $digestHash = md5(
            md5($username . ':' . self::REALM . ':' . $password) . ':' .
            $nonce . ':' .
            $nc . ':' .
            $cnonce . ':' .
            'auth:' .
            md5('GET' . ':' . '/')
        );

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'GET',
            'HTTP_AUTHENTICATION' => 'Digest username="'.$username.'", realm="' . self::REALM . '", nonce="' . $nonce . '", uri="/", response="' . $digestHash . '", opaque="' . $opaque . '", qop=auth,nc='.$nc.',cnonce="' . $cnonce . '"',
        ));
        
        $this->auth->setHTTPRequest($request);
        $this->auth->init();
        
        $this->assertTrue($this->auth->validateA1(md5($username . ':' . self::REALM . ':' . $password)),'Authentication is deemed invalid through validateA1');
        $this->assertTrue($this->auth->validatePassword($password),'Authentication is deemed invalid through validatePassword');

    }

    public function testInvalidDigest() {
        
        list($nonce,$opaque) = $this->getServerTokens();

        $username = 'admin';
        $password = 12345;
        $nc = '00002';
        $cnonce = uniqid();

        $digestHash = md5(
            md5($username . ':' . self::REALM . ':' . $password) . ':' .
            $nonce . ':' .
            $nc . ':' .
            $cnonce . ':' .
            'auth:' .
            md5('GET' . ':' . '/')
        );

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'GET',
            'PHP_AUTH_DIGEST' => 'username="'.$username.'", realm="' . self::REALM . '", nonce="' . $nonce . '", uri="/", response="' . $digestHash . '", opaque="' . $opaque . '", qop=auth,nc='.$nc.',cnonce="' . $cnonce . '"',
        ));
        
        $this->auth->setHTTPRequest($request);
        $this->auth->init();
        
        $this->assertFalse($this->auth->validateA1(md5($username . ':' . self::REALM . ':' . ($password . 'randomness'))),'Authentication is deemed invalid through validateA1');

    }

    public function testDigestAuthInt() {
        
        list($nonce,$opaque) = $this->getServerTokens();

        $username = 'admin';
        $password = 12345;
        $nc = '00003';
        $cnonce = uniqid();

        $digestHash = md5(
            md5($username . ':' . self::REALM . ':' . $password) . ':' .
            $nonce . ':' .
            $nc . ':' .
            $cnonce . ':' .
            'auth-int:' .
            md5('POST' . ':' . '/' . ':' . md5('body'))
        );

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'POST',
            'PHP_AUTH_DIGEST' => 'username="'.$username.'", realm="' . self::REALM . '", nonce="' . $nonce . '", uri="/", response="' . $digestHash . '", opaque="' . $opaque . '", qop=auth-int,nc='.$nc.',cnonce="' . $cnonce . '"',
        ));
        $request->setBody('body');
        
        $this->auth->setHTTPRequest($request);
        $this->auth->init();
        
        $this->assertTrue($this->auth->validateA1(md5($username . ':' . self::REALM . ':' . $password)),'Authentication is deemed invalid through validateA1');

    }

    private function getServerTokens() {

        $this->auth->requireLogin();

        $test = preg_match('/Digest realm="'.self::REALM.'",qop="auth,auth-int",nonce="([0-9a-f]*)",opaque="([0-9a-f]*)"/',
            $this->response->headers['WWW-Authenticate'],$matches);

        $this->assertTrue($test==true,'The WWW-Authenticate response didn\'t match our pattern');

        $nonce = $matches[1];
        $opaque = $matches[2];

        // Reset our environment
        $this->setUp();
        
        return array($nonce,$opaque);

    }

}

?>
