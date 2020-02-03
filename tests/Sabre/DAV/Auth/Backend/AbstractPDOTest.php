<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

abstract class AbstractPDOTest extends \PHPUnit\Framework\TestCase
{
    use \Sabre\DAV\DbTestHelperTrait;

    public function setup(): void
    {
        $this->dropTables('users');
        $this->createSchema('users');

        $this->getPDO()->query(
            "INSERT INTO users (username,digesta1) VALUES ('user','hash')"
        );
    }

    public function testConstruct()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertTrue($backend instanceof PDO);
    }

    /**
     * @depends testConstruct
     */
    public function testUserInfo()
    {
        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $this->assertNull($backend->getDigestHash('realm', 'blabla'));

        $expected = 'hash';

        $this->assertEquals($expected, $backend->getDigestHash('realm', 'user'));
    }
}
