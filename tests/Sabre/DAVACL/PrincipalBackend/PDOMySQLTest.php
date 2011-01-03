<?php

require_once 'Sabre/TestUtil.php';

class Sabre_DAVACL_PrincipalBackend_PDOMySQLTest extends Sabre_DAVACL_PrincipalBackend_AbstractPDOTest {

    function getPDO() {

        if (!SABRE_HASMYSQL) $this->markTestSkipped('MySQL driver is not available, or not properly configured');
        $pdo = Sabre_TestUtil::getMySQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to MySQL database');
        $pdo->query("DROP TABLE IF EXISTS principals");
        $pdo->query("
create table principals (
	id integer unsigned not null primary key auto_increment,
	uri varchar(50),
    email varchar(80),
    displayname VARCHAR(80),
	unique(uri)
);");

        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/user','user@example.org','User')");

        return $pdo;

    }

}
