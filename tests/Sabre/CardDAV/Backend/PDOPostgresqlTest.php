<?php

namespace Sabre\CardDAV\Backend;

require_once 'Sabre/TestUtil.php';

class PDOPostgreSQLTest extends AbstractPDOTest {

    /**
     * @return PDO
     */
    public function getPDO() {

        if (!SABRE_HASPGSQL) $this->markTestSkipped('PostgreSQL driver is not available, or not properly configured');

        $pdo = \Sabre\TestUtil::getPostgreSQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to PostgreSQL database');

        $pdo->query("DROP TABLE IF EXISTS cards, addressbookchanges, addressbooks");

        $queries = explode(
            ';',
            file_get_contents(__DIR__ . '/../../../../examples/sql/pgsql.addressbook.sql')
        );

        foreach($queries as $query) {
            $query = trim($query," \r\n\t");
            if ($query)
                $pdo->exec($query);
        }
        return $pdo;

    }

}

