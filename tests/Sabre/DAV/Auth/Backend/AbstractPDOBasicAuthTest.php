<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;

abstract class AbstractPDOBasicAuthTest extends \PHPUnit\Framework\TestCase
{
    use \Sabre\DAV\DbTestHelperTrait;

    public function setup(): void
    {
        $this->dropTables('users');
        $this->createSchema('users');

        // The supplied hash is a salted bcrypt hash of the plaintext : 'password'
        $this->getPDO()->query(
            "INSERT INTO users (username,digesta1) VALUES ('user','\$2b\$12\$IwetRH4oj6.AWFGGVy8fpet7Pgp1TafspB6iq1/fiLDxfsGZfi2jS')"
        );
        $this->getPDO()->query(
            "INSERT INTO users (username,digesta1) VALUES ('prefix_user','bcrypt\$\$2b\$12\$IwetRH4oj6.AWFGGVy8fpet7Pgp1TafspB6iq1/fiLDxfsGZfi2jS')"
        );
    }

    public function testConstruct()
    {
        $pdo = $this->getPDO();
        $backend = new PDOBasicAuth($pdo);
        self::assertTrue($backend instanceof PDOBasicAuth);
    }

    public function testCheckNoHeaders()
    {
        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $options = [
            'tableName' => 'users',
            'digestColumn' => 'digesta1',
            'uuidColumn' => 'username',
        ];
        $pdo = $this->getPDO();
        $backend = new PDOBasicAuth($pdo, $options);

        self::assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckUnknownUser()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_USER' => 'unkown_user',
            'PHP_AUTH_PW' => 'wrongpassword',
        ]);
        $response = new HTTP\Response();

        $options = [
            'tableName' => 'users',
            'digestColumn' => 'digesta1',
            'uuidColumn' => 'username',
        ];
        $pdo = $this->getPDO();
        $backend = new PDOBasicAuth($pdo, $options);

        self::assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckAuthenticationFailure()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'wrongpassword',
        ]);
        $response = new HTTP\Response();

        $options = [
            'tableName' => 'users',
            'digestColumn' => 'digesta1',
            'uuidColumn' => 'username',
        ];
        $pdo = $this->getPDO();
        $backend = new PDOBasicAuth($pdo, $options);

        self::assertFalse(
            $backend->check($request, $response)[0]
        );
    }

    public function testCheckSuccess()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'password',
        ]);
        $response = new HTTP\Response();

        $options = [
            'tableName' => 'users',
            'digestColumn' => 'digesta1',
            'uuidColumn' => 'username',
        ];
        $pdo = $this->getPDO();
        $backend = new PDOBasicAuth($pdo, $options);
        self::assertEquals(
            [true, 'principals/user'],
            $backend->check($request, $response)
        );
    }

    public function testPrefixSuccess()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'PHP_AUTH_USER' => 'prefix_user',
            'PHP_AUTH_PW' => 'password',
        ]);
        $response = new HTTP\Response();

        $options = [
            'tableName' => 'users',
            'digestColumn' => 'digesta1',
            'uuidColumn' => 'username',
            'digestPrefix' => 'bcrypt$',
        ];
        $pdo = $this->getPDO();
        $backend = new PDOBasicAuth($pdo, $options);
        self::assertEquals(
            [true, 'principals/prefix_user'],
            $backend->check($request, $response)
        );
    }

    public function testRequireAuth()
    {
        $request = new HTTP\Request('GET', '/');
        $response = new HTTP\Response();

        $pdo = $this->getPDO();
        $backend = new PDOBasicAuth($pdo);
        $backend->setRealm('writing unittests on a saturday night');
        $backend->challenge($request, $response);

        self::assertEquals(
            'Basic realm="writing unittests on a saturday night", charset="UTF-8"',
            $response->getHeader('WWW-Authenticate')
        );
    }
}
