<?php

namespace Sabre\DAV\Locks\Backend;

require_once 'Sabre/TestUtil.php';

class PDOSqliteTest extends AbstractTest {

    function getBackend() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('Sqlite driver is not available, or it was not properly configured');
        $pdo = \Sabre\TestUtil::getSqliteDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to Sqlite database');
        $pdo->query('DROP TABLE IF EXISTS locks');
        $queries = file_get_contents(__DIR__ . '/../../../../../examples/sql/sqlite.locks.sql');

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
