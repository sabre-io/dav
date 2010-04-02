<?php

require_once 'Sabre/TestUtil.php';

class Sabre_DAV_Auth_Backend_PDOMySQLTest extends Sabre_DAV_Auth_Backend_AbstractPDOTest {

    function getPDO() {

        if (!SABRE_HASMYSQL) $this->markTestSkipped('MySQL driver is not available, or not properly configured');
        $pdo = Sabre_TestUtil::getMySQLDB();
        $pdo->query("DROP TABLE IF EXISTS users");
        $pdo->query("
create table users (
	id integer unsigned not null primary key auto_increment,
	username varchar(50),
	digesta1 varchar(32),
	unique(username)
);");

        $pdo->query("INSERT INTO users (username,digesta1) VALUES ('user','hash')");

        return $pdo;

    }

}
