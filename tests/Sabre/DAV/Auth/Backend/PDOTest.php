<?php

class Sabre_DAV_Auth_Backend_PDOTest extends PHPUnit_Framework_TestCase {

    function tearDown() {

        if (file_exists(SABRE_TEMPDIR . '/pdobackend')) unlink(SABRE_TEMPDIR . '/pdobackend');
        if (file_exists(SABRE_TEMPDIR . '/pdobackend2')) unlink(SABRE_TEMPDIR . '/pdobackend2');

    }

    function testConstruct() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $pdo = new PDO('sqlite:'.SABRE_TEMPDIR.'/pdobackend');
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $pdo->query('CREATE TABLE users (username TEXT, digesta1 TEXT)');
        $pdo->query('INSERT INTO users VALUES ("user","hash")');

        $backend = new Sabre_DAV_Auth_Backend_PDO($pdo);
        $this->assertTrue($backend instanceof Sabre_DAV_Auth_Backend_PDO);

    }

    /**
     * @depends testConstruct
     */
    function testUserInfo() {

        $pdo = new PDO('sqlite:'.SABRE_TEMPDIR.'/pdobackend2');
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $pdo->query('CREATE TABLE users (username TEXT, digesta1 TEXT)');
        $pdo->query('INSERT INTO users VALUES ("user","hash")');

        $backend = new Sabre_DAV_Auth_Backend_PDO($pdo);

        $this->assertEquals(array(array('userId'=>'user')), $backend->getUsers());
        $this->assertFalse($backend->getUserInfo('realm','blabla'));
        $this->assertEquals(array('userId'=>'user','digestHash'=>'hash'), $backend->getUserInfo('realm','user'));

    }

}
