<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

require_once __DIR__.'/../../../HTTP/ResponseMock.php';

abstract class AbstractMongoDBTest extends \PHPUnit\Framework\TestCase
{
    use \Sabre\DAV\MongoTestHelperTrait;

    public function setUp()
    {
        $this->db = $this->getMongo();
        $this->db->drop();

        $username = 'johndoe';
        $salt = str_replace('+', '.', base64_encode('abcdefghijklmnopqrstuv'));
        $pw = crypt('xxx', '$2y$10$'.$salt.'$');

        $userId = new \MongoDB\BSON\ObjectId();

        $this->db->users->insertOne([
        '_id' => $userId,
        'username' => $username,
        'password' => $pw,
      ]);
        $this->userPrincipal = 'principals/users/'.(string) $userId;
    }

    public function testConstruct()
    {
        $mongo = new Mongo($this->db);
        $this->assertTrue($mongo instanceof Mongo);
    }

    public function testLoginPrimary()
    {
        $backend = new MockMongo($this->db);
        $this->assertTrue($backend->validateUserPass('johndoe', 'xxx'));
    }

    public function testLoginInvalidPassword()
    {
        $backend = new MockMongo($this->db);
        $this->assertFalse($backend->validateUserPass('johndoe', 'zzz'));
    }
}
class MockMongo extends Mongo
{
    public function validateUserPass($username, $password)
    {
        return parent::validateUserPass($username, $password);
    }
}
