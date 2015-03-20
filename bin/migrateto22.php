#!/usr/bin/env php
<?php

echo "SabreDAV migrate script for version 2.2\n";

if ($argc<2) {

    echo <<<HELLO

This script help you migrate from a pre-2.2 database to 2.2 and later

Changes:
  * Values in the propertystorage table are now serialized using PHP's
    serialize()

Keep in mind that ALTER TABLE commands will be executed. If you have a large
dataset this may mean that this process takes a while.

Lastly: Make a back-up first. This script has been tested, but the amount of
potential variants are extremely high, so it's impossible to deal with every
possible situation.

In the worst case, you will lose all your data. This is not an overstatement.

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

echo "Upgrading 'propertystorage'\n";
$addValueType = false;
try {
    $result = $pdo->query('SELECT * FROM propertystorage LIMIT 1');
    $row = $result->fetch(\PDO::FETCH_ASSOC);

    if (!$row) {
        echo "No data in table. Going to try to add the uid field anyway.\n";
        $addValueType = true;
    } elseif (array_key_exists('valuetype', $row)) {
        echo "valuetype field exists. Assuming that this part of the migration has\n";
        echo "Already been completed.\n";
    } else {
        echo "2.1 schema detected. Going to perform upgrade.\n";
        $addValueType = true;
    }

} catch (Exception $e) {
    echo "Could not find a propertystorage table. Skipping this part of the\n";
    echo "upgrade.\n";
}

if ($addValueType) {

    switch($driver) {
        case 'mysql' :
            $pdo->exec('ALTER TABLE propertystorage ADD valuetype INT UNSIGNED');
            break;
        case 'sqlite' :
            $pdo->exec('ALTER TABLE propertystorage ADD valuetype INT');

            break;
    }

    $pdo->exec('UPDATE propertystorage SET valuetype = 1 WHERE valuetype IS NULL ');

}

echo "Done.\n";
echo "Upgrade to 2.2 schema completed.\n";
