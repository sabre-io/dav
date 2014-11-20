<?php

namespace Sabre\DAV\Auth\Backend;

require_once 'Sabre/TestUtil.php';

class PDOPostgreSQLTest extends AbstractPDOTest {

    function getPDO() {

        if (!SABRE_HASPGSQL) $this->markTestSkipped('PostgreSQL driver is not available, or not properly configured');
        $pdo = \Sabre\TestUtil::getPostgreSQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to PostgreSQL database');
        $pdo->query("DROP TABLE IF EXISTS users");
        $pdo->query("
create table users (
  id serial not null primary key,
  username varchar(50) unique,
  digesta1 varchar(32),
  email varchar(80),
  displayname varchar(80)
);");

        $pdo->query("INSERT INTO users (username,digesta1,email,displayname) VALUES ('user','hash','user@example.org','User')");

        return $pdo;

    }

}
