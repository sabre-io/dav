<?php

namespace Sabre\DAV\PropertyStorage\Backend;

class PDOPgSqlTest extends AbstractPDOTest {

    function getPDO() {

        if (!SABRE_HASPGSQL) $this->markTestSkipped('PGSQL driver is not available, or not properly configured');
        $pdo = \Sabre\TestUtil::getPgSqlDB();
        if (!$pdo) $this->markTestSkipped('Pos is not enabled');


        $setupSql = file_get_contents(__DIR__ . '/../../../../../examples/sql/pgsql.propertystorage.sql');
        // Sloppy multi-query, but it works
        $setupSql = explode(';', $setupSql);

        $pdo->exec('DROP TABLE IF EXISTS propertystorage');

        foreach ($setupSql as $sql) {

            if (!trim($sql)) continue;
            $pdo->exec($sql);

        }
        $pdo->exec("INSERT INTO propertystorage (path, name, value, valuetype) VALUES ('dir', '{DAV:}displayname', 'Directory', 1)");

        return $pdo;

    }

}
