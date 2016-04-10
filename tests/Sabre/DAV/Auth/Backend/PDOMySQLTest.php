<?php

namespace Sabre\DAV\Auth\Backend;

require_once 'Sabre/TestUtil.php';

class PDOMySQLTest extends AbstractPDOTest {

    function getPDO() {

        if (!SABRE_HASMYSQL) $this->markTestSkipped('MySQL driver is not available, or not properly configured');
        $pdo = \Sabre\TestUtil::getMySQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to MySQL database');

        $sql = file_get_contents(__DIR__ . '/../../../../examples/sql/mysql.users.sql');

        $pdo->query("DROP TABLE IF EXISTS users");
        $pdo->query($sql);
        $pdo->query("INSERT INTO users (username,digesta1) VALUES ('user','hash','user@example.org','User')");

        return $pdo;

    }

}
