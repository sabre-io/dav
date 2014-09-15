<?php

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;
use Sabre\HTTP;


require_once 'Sabre/TestUtil.php';

class PDOPostgreSQLTest extends AbstractPDOTest {

    function getPDO() {

        if (!SABRE_HASPGSQL) $this->markTestSkipped('PostgreSQL driver is not available, or not properly configured');
        $pdo = \Sabre\TestUtil::getPostgreSQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to PostgreSQL database');
        $pdo->query("DROP TABLE IF EXISTS principals");
        $pdo->query("
                create table principals (
                  id serial not null primary key,
                  uri varchar(50) unique,
                  email varchar(80),
                  displayname VARCHAR(80),
                  vcardurl VARCHAR(80)
                );");

        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/user','user@example.org','User')");
        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/group','group@example.org','Group')");
        
        $pdo->query("DROP TABLE IF EXISTS groupmembers");
        $pdo->query("CREATE TABLE groupmembers (
                id SERIAL NOT NULL PRIMARY KEY,
                principal_id INTEGER NOT NULL,
                member_id INTEGER NOT NULL,
                UNIQUE(principal_id, member_id)
                );");

        $pdo->query("INSERT INTO groupmembers (principal_id,member_id) VALUES (2,1)");

        return $pdo;

    }

}
