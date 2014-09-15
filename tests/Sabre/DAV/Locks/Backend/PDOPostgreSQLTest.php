<?php

namespace Sabre\DAV\Locks\Backend;

require_once 'Sabre/TestUtil.php';

class PDOPostgreSQLTest extends AbstractTest {

    function getBackend() {

        if (!SABRE_HASPGSQL) $this->markTestSkipped('PostgreSQL driver is not available, or it was not properly configured');
        $pdo = \Sabre\TestUtil::getPostgreSQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to PostgreSQL database');
        $pdo->query('DROP TABLE IF EXISTS locks;');
        $pdo->query("
CREATE TABLE locks (
  id SERIAL NOT NULL PRIMARY KEY,
  owner VARCHAR(100),
  timeout INTEGER,
  created INTEGER,
  token VARCHAR(100),
  scope SMALLINT,
  depth SMALLINT,
  uri TEXT
);");

        $backend = new PDO($pdo);
        return $backend;

    }

}
