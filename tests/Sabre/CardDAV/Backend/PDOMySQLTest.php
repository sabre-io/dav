<?php

namespace Sabre\CardDAV\Backend;

require_once 'Sabre/TestUtil.php';

class PDOMySQLTest extends AbstractPDOTest {

    /**
     * @return PDO
     */
    public function getPDO() {

        if (!SABRE_HASMYSQL) $this->markTestSkipped('MySQL driver is not available, or not properly configured');

        $pdo = \Sabre\TestUtil::getMySQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to MySQL database');

        $pdo->query("DROP TABLE IF EXISTS addressbooks");
        $pdo->query("DROP TABLE IF EXISTS cards");
        $pdo->query("
CREATE TABLE addressbooks (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARCHAR(255),
    displayname VARCHAR(255),
    uri VARCHAR(100),
    description TEXT,
    ctag INT(11) UNSIGNED NOT NULL DEFAULT '1'
);
");

        $pdo->query("
INSERT INTO addressbooks
    (principaluri, displayname, uri, description, ctag)
VALUES
    ('principals/user1', 'book1', 'book1', 'addressbook 1', 1);
");

        $pdo->query("
CREATE TABLE cards (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    addressbookid INT(11) UNSIGNED NOT NULL,
    carddata TEXT,
    uri VARCHAR(100),
    lastmodified INT(11) UNSIGNED
);
");

        $pdo->query("
INSERT INTO cards
    (addressbookid, carddata, uri, lastmodified)
VALUES
    (1, 'card1', 'card1', 0);
");
        return $pdo;

    }

}

