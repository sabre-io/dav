#!/usr/bin/env php
<?php

echo "SabreDAV migrate script for version 1.9\n";

if ($argc<2) {

    echo <<<HELLO

This script help you migrate from a pre-1.9 database to 1.9 and later\n
The 'calendars' tables will be upgraded, and a new table: calendar_changes
will be added.

If you don't use the default PDO CalDAV backend, it's pointless to run this
script.

Keep in mind that ALTER TABLE commands will be executed. If you have a large
calendars table, this may mean that this process takes a while.

Usage:

php {$argv[0]} [pdo-dsn] [username] [password]

For example:

php {$argv[0]} "mysql:host=localhost;dbname=sabredav" root password
php {$argv[0]} sqlite:data/sabredav.db

HELLO;

    exit();

}

// There's a bunch of places where the autoloader could be, so we'll try all of
// them.
$paths = array(
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
);

foreach($paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}

$dsn = $argv[1];
$user = isset($argv[2])?$argv[2]:null;
$pass = isset($argv[3])?$argv[3]:null;

echo "Connecting to database: " . $dsn . "\n";

$pdo = new PDO($dsn, $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

switch($driver) {

    case 'mysql' :
        echo "Detected MySQL.\n";
        break;
    case 'sqlite' :
        echo "Detected SQLite.\n";
        break;
    default :
        echo "Error: unsupported driver: " . $driver . "\n";
        die(-1);
}

foreach(['calendar', 'addressbook'] as $itemType) {

    $tableName = $itemType . 's'; 
    $tableNameOld = $tableName . '_old';
    $changesTable = $itemType . 'changes';

    echo "Upgrading '$tableName'\n";

    // The only cross-db way to do this, is to just fetch a single record.
    $row = $pdo->query("SELECT * FROM $tableName LIMIT 1")->fetch();

    if (!$row) {

        echo "No records were found in the '$tableName' table.\n";
        echo "\n";
        echo "We're going to rename the old table to $tableNameOld (just in case).\n";
        echo "and re-create the new table.\n";

        switch($driver) {

            case 'mysql' :
                $pdo->exec("RENAME TABLE $tableName TO $tableNameOld");
                switch($itemType) {
                    case 'calendar' :
                        $pdo->exec("
            CREATE TABLE calendars (
                id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                principaluri VARCHAR(100),
                displayname VARCHAR(100),
                uri VARCHAR(200),
                synctoken INT(11) UNSIGNED NOT NULL DEFAULT '0',
                description TEXT,
                calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
                calendarcolor VARCHAR(10),
                timezone TEXT,
                components VARCHAR(20),
                transparent TINYINT(1) NOT NULL DEFAULT '0',
                UNIQUE(principaluri, uri)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
                        ");
                        break;
                    case 'addressbook' :
                        $pdo->exec("
            CREATE TABLE addressbooks (
                id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                principaluri VARCHAR(255),
                displayname VARCHAR(255),
                uri VARCHAR(200),
                description TEXT,
                synctoken INT(11) UNSIGNED NOT NULL DEFAULT '1',
                UNIQUE(principaluri, uri)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
                        ");
                        break;
                }

            case 'sqlite' :

                $pdo->exec("ALTER TABLE $tableName RENAME TO $tableNameOld");

                switch($itemType) {
                    case 'calendar' :
                        $pdo->exec("
            CREATE TABLE calendars (
                id integer primary key asc,
                principaluri text,
                displayname text,
                uri text,
                synctoken integer,
                description text,
                calendarorder integer,
                calendarcolor text,
                timezone text,
                components text,
                transparent bool
            );
                        ");
                        break;
                    case 'addressbook' :
                        $pdo->exec("
            CREATE TABLE addressbooks (
                id integer primary key asc,
                principaluri text,
                displayname text,
                uri text,
                description text,
                synctoken integer
            );
                        ");

                        break;
                }

        }
        echo "Creation of 1.9 $tableName table is complete\n";

    } else {

        // Checking if there's a synctoken field already.
        if (array_key_exists('synctoken', $row)) {
            echo "The 'synctoken' field already exists in the $tableName table.\n";
            echo "It's likely you already upgraded, so we're simply leaving\n";
            echo "the $tableName table alone\n";
        } else {

            echo "1.8 table schema detected\n";
            switch($driver) {

                case 'mysql' :
                    $pdo->exec("ALTER TABLE $tableName ADD synctoken INT(11) UNSIGNED NOT NULL DEFAULT '0'");
                    $pdo->exec("ALTER TABLE $tableName DROP ctag");
                    break;
                case 'sqlite' :
                    $pdo->exec("ALTER TABLE $tableName ADD synctoken integer");
                    echo "Note: there's no easy way to remove fields in sqlite.\n";
                    echo "The ctag field is no longer used, but it's kept in place\n";
                    break;

            }

            echo "Upgraded '$tableName' to 1.9 schema.\n";

        }

    }

    try {
        $pdo->query("SELECT * FROM $changesTable LIMIT 1");

        echo "'$changesTable' already exists. Assuming that this part of the\n";
        echo "upgrade was already completed.\n";

    } catch (Exception $e) {
        echo "Creating '$changesTable' table.\n";

        switch($driver) {

            case 'mysql' :
                $pdo->exec("
    CREATE TABLE $changesTable (
        id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
        uri VARCHAR(200) NOT NULL,
        synctoken INT(11) UNSIGNED NOT NULL,
        {$itemType}id INT(11) UNSIGNED NOT NULL,
        isdelete TINYINT(1) NOT NULL,
        INDEX {$itemType}id_synctoken ({$itemType}id, synctoken)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

                ");
                break;
            case 'sqlite' :
                $pdo->exec("

    CREATE TABLE $changesTable (
        id integer primary key asc,
        uri text,
        synctoken integer,
        {$itemType}id integer,
        isdelete bool
    );

                ");
                $pdo->exec("CREATE INDEX {$itemType}id_synctoken ON $changesTable ({$itemType}id, synctoken);");
                break;

        }

    }

}

echo "Upgrade to 1.9 schema completed.\n";
