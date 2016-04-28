<?php

namespace Sabre\DAV\Locks\Backend;

require_once 'Sabre/TestUtil.php';

class PDOPgSqlTest extends AbstractTest {

    function getBackend() {

        if (!SABRE_HASPGSQL) $this->markTestSkipped('PgSQL driver is not available, or it was not properly configured');
        $pdo = \Sabre\TestUtil::getPgSqlDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to PgSQL database');
        $pdo->query('DROP TABLE IF EXISTS locks');
        $queries = file_get_contents(__DIR__ . '/../../../../../examples/sql/pgsql.locks.sql');

        foreach (explode(';', $queries) as $query) {
            if (trim($query) === '') {
                continue;
            }
            $pdo->query($query);
        }

        $backend = new PDO($pdo);
        return $backend;

    }

}
