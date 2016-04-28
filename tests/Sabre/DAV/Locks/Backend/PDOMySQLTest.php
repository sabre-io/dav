<?php

namespace Sabre\DAV\Locks\Backend;

require_once 'Sabre/TestUtil.php';

class PDOMySQLTest extends AbstractTest {

    function getBackend() {

        if (!SABRE_HASMYSQL) $this->markTestSkipped('MySQL driver is not available, or it was not properly configured');
        $pdo = \Sabre\TestUtil::getMySQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to MySQL database');

        $pdo->query('DROP TABLE IF EXISTS locks;');
        $queries = file_get_contents(__DIR__ . '/../../../../../examples/sql/mysql.locks.sql');
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
