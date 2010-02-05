<?php

class Sabre_DAV_Auth_Backend_PDOTest extends PHPUnit_Framework_TestCase {

    function tearDown() {

        if (file_exists('temp/pdobackend')) unlink('temp/pdobackend');
        if (file_exists('temp/pdobackend2')) unlink('temp/pdobackend2');

    }

    function testConstruct() {

        $pdo = new PDO('sqlite:temp/pdobackend');
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

        $pdo = new PDO('sqlite:temp/pdobackend2');
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $pdo->query('CREATE TABLE users (username TEXT, digesta1 TEXT)');
        $pdo->query('INSERT INTO users VALUES ("user","hash")');

        $backend = new Sabre_DAV_Auth_Backend_PDO($pdo);

        $this->assertEquals(array(array('userId'=>'user')), $backend->getUsers());
        $this->assertFalse($backend->getUserInfo('realm','blabla'));
        $this->assertEquals(array('userId'=>'user','digestHash'=>'hash'), $backend->getUserInfo('realm','user'));

    }

}
