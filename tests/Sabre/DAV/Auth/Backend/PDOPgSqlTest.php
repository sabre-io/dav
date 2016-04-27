<?php

namespace Sabre\DAV\Auth\Backend;

class PDOPgSqlTest extends AbstractPDOTest {

    function getPDO() {

        if (!SABRE_HASPGSQL) $this->markTestSkipped('PGSQL driver is not available, or not properly configured');
        $pdo = \Sabre\TestUtil::getPgSqlDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to PgSql database');

        $sql = file_get_contents(__DIR__ . '/../../../../../examples/sql/pgsql.users.sql');

        $pdo->query("DROP TABLE IF EXISTS users");
        foreach(explode(';', $sql) as $statement) {
            if(trim($statement) === '') continue;;
            $pdo->query($statement); // Yup
        }
        $pdo->query("INSERT INTO users (username,digesta1) VALUES ('user','hash')");

        return $pdo;

    }

}
