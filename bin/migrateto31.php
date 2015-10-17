#!/usr/bin/env php
<?php

echo "SabreDAV migrate script for version 3.1\n";

if ($argc<2) {

    echo <<<HELLO

This script help you migrate from a 3.0 database to 3.1 and later

Changes:
* Created a new calendar_instances table to support calendar sharing.
* Remove a lot of columns from calendars.

Keep in mind that ALTER TABLE commands will be executed. If you have a large
dataset this may mean that this process takes a while.

Make a back-up first. This script has been tested, but the amount of
potential variants are extremely high, so it's impossible to deal with every
possible situation.

In the worst case, you will lose all your data. This is not an overstatement.

Lastly, if you are upgrading from an older version than 3.0, make sure you run
the earlier migration script first. Migration scripts must be ran in order.

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
$paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach($paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}

$dsn = $argv[1];
$user = isset($argv[2])?$argv[2]:null;
$pass = isset($argv[3])?$argv[3]:null;

$backupPostfix = time();

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

echo "Creating 'calendar_instances'\n";
$addValueType = false;
try {
    $result = $pdo->query('SELECT * FROM calendar_instances LIMIT 1');
    $result->fetch(\PDO::FETCH_ASSOC);
    echo "calendar_instances exists. Assuming this part of the migration has already been done.\n";
} catch (Exception $e) {
    echo "calendar_instances does not yet exist. Creating table and migrating data.\n";

    switch($driver) {
        case 'mysql' :
            $pdo->exec(<<<SQL
CREATE TABLE calendar_instances (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendarid INTEGER UNSIGNED NOT NULL,
    principaluri VARBINARY(100),
    access TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = owner, 2 = readwrite, 3 = read',
    displayname VARCHAR(100),
    uri VARBINARY(200),
    description TEXT,
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARBINARY(10),
    timezone TEXT,
    transparent TINYINT(1) NOT NULL DEFAULT '0',
    UNIQUE(principaluri, uri)
);
SQL
        );
            $pdo->exec("
INSERT INTO calendar_instances
    (
        calendarid,
        principaluri,
        access,
        displayname,
        uri,
        description,
        calendarorder,
        calendarcolor,
        transparent
    )
SELECT
    id,
    principaluri,
    1,
    displayname,
    uri,
    description,
    calendarorder,
    calendarcolor,
    transparent
FROM calendars
"); 
            break;
        case 'sqlite' :
            $pdo->exec(<<<SQL
CREATE TABLE calendar_instances (
    id integer primary key asc,
    calendarid integer,
    principaluri text,
    access integer COMMENT '1 = owner, 2 = readwrite, 3 = read',
    displayname text,
    uri text,
    description text,
    calendarorder integer,
    calendarcolor text,
    timezone text,
    transparent bool
);
SQL
        );
            $pdo->exec("
INSERT INTO calendar_instances
    (
        calendarid,
        principaluri,
        access,
        displayname,
        uri,
        description,
        calendarorder,
        calendarcolor,
        transparent
    )
SELECT
    id,
    principaluri,
    1,
    displayname,
    uri,
    description,
    calendarorder,
    calendarcolor,
    transparent
FROM calendars
"); 
            break;
    }

}
try {
    $result = $pdo->query('SELECT * FROM calendars LIMIT 1');
    $row = $result->fetch(\PDO::FETCH_ASSOC);

    if (!$row) {
        echo "Source table is empty.\n";
        $migrateCalendars = true;
    } 

    $columnCount = count($row);
    if ($columnCount === 3) {
        echo "The calendars table has 3 columns already. Assuming this part of the migration was already done.\n";
        $migrateCalendars = false;
    } else {
        echo "The calendars table has " . $columnCount . " columns.\n";
        $migrateCalendars = true;
    }

} catch (Exception $e) {
    echo "calendars table does not exist. This is a major problem. Exiting.\n";
    exit(-1);
}

if ($migrateCalendars) {

    $calendarBackup = 'calendars_3_0_' . $backupPostfix;
    echo "Backing up 'calendars' to '", $calendarBackup, "'\n";

    switch($driver) {
        case 'mysql' :
            $pdo->exec('RENAME TABLE calendars TO ' . $calendarBackup);
            break;
        case 'sqlite' :
            $pdo->exec('ALTER TABLE calendars RENAME TO ' . $calendarBackup);
            break;

    }

    echo "Creating new calendars table.\n";
    switch($driver) {
        case 'mysql' :
            $pdo->exec(<<<SQL
CREATE TABLE calendars (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    synctoken INTEGER UNSIGNED NOT NULL DEFAULT '1',
    components VARBINARY(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL
);
            break;
        case 'sqlite' :
            $pdo->exec(<<<SQL
CREATE TABLE calendars (
    id integer primary key asc,
    synctoken integer,
    components text
);
SQL
        );
            break;

    }

    echo "Migrating data from old to new table\n";

    $pdo->exec(<<<SQL
INSERT INTO calendars (id, synctoken, components) SELECT id, synctoken, components FROM $calendarBackup
SQL
    );

}


echo "Upgrade to 3.1 schema completed.\n";
