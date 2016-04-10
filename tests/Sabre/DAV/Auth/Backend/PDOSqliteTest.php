<?php

namespace Sabre\DAV\Auth\Backend;

require_once 'Sabre/DAV/Auth/Backend/AbstractPDOTest.php';

class PDOSqliteTest extends AbstractPDOTest {

    function tearDown() {

        if (file_exists(SABRE_TEMPDIR . '/pdobackend')) unlink(SABRE_TEMPDIR . '/pdobackend');
        if (file_exists(SABRE_TEMPDIR . '/pdobackend2')) unlink(SABRE_TEMPDIR . '/pdobackend2');

    }

    function getPDO() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $pdo = new \PDO('sqlite:' . SABRE_TEMPDIR . '/pdobackend');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $sql = file_get_contents(__DIR__ . '/../../../../../examples/sql/sqlite.users.sql');
        $pdo->query($sql);
        $pdo->query('INSERT INTO users (username, digesta1) VALUES ("user","hash")');

        return $pdo;

    }

}
