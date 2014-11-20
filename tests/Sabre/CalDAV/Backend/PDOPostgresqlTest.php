<?php

namespace Sabre\CalDAV\Backend;

require_once 'Sabre/TestUtil.php';
require_once 'Sabre/CalDAV/TestUtil.php';
require_once 'Sabre/CalDAV/Backend/AbstractPDOTest.php';

class PDOPostgreSQLTest extends AbstractPDOTest {

    function setup() {

        if (!SABRE_HASPGSQL) $this->markTestSkipped('PostgreSQL driver is not available, or not properly configured');
        $pdo = \Sabre\TestUtil::getPostgreSQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to PostgreSQL database');

        $pdo->query('DROP TABLE IF EXISTS calendarobjects, calendarchanges, calendarsubscriptions, schedulingobjects, calendars');

        $queries = explode(
            ';',
            file_get_contents(__DIR__ . '/../../../../examples/sql/pgsql.calendars.sql')
        );

        foreach($queries as $query) {
            $query = trim($query," \r\n\t");
            if ($query)
                $pdo->exec($query);
        }
        $this->pdo = $pdo;

    }

    function teardown() {

        $this->pdo = null;

    }

}
