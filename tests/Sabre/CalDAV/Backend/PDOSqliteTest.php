<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;

require_once 'Sabre/CalDAV/Backend/AbstractPDOTest.php';

class PDOSQLiteTest extends AbstractPDOTest {

    function setup() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $this->pdo = CalDAV\TestUtil::getSQLiteDB();

    }

    function teardown() {

        $this->pdo = null;
        unlink(SABRE_TEMPDIR . '/testdb.sqlite');

    }

}
