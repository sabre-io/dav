<?php

/**
 * This server combines both CardDAV and CalDAV functionality into a single
 * server. It is assumed that the server runs at the root of a HTTP domain (be
 * that a domainname-based vhost or a specific TCP port.
 *
 * This example also assumes that you're using SQLite and the database has
 * already been setup (along with the database tables).
 *
 * You may choose to use MySQL instead, just change the PDO connection
 * statement.
 */

/*
 * UTC or GMT is easy to work with, and usually recommended for any
 * application.
 */
date_default_timezone_set('UTC');

/**
 * Make sure this setting is turned on and reflect the root url for your WebDAV
 * server.
 *
 * This can be for example the root / or a complete path to your server script.
 */
// $baseUri = '/';

/**
 * Database.
 *
 * Feel free to switch this to MySQL, it will definitely be better for higher
 * concurrency.
 */
$pdo = new \PDO('sqlite:data/db.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Autoloader
require_once 'vendor/autoload.php';

/**
 * The backends. Yes we do really need all of them.
 *
 * This allows any developer to subclass just any of them and hook into their
 * own backend systems.
 */
$authBackend = new \Sabre\DAV\Auth\Backend\PDO($pdo);
$principalBackend = new \Sabre\DAVACL\PrincipalBackend\PDO($pdo);
$carddavBackend = new \Sabre\CardDAV\Backend\PDO($pdo);
$caldavBackend = new \Sabre\CalDAV\Backend\PDO($pdo);

/**
 * PSR-3 Logging facility.
 */
$logger = new \Monolog\Logger('SabreDav');
$logger->pushHandler(new \Monolog\Handler\RotatingFileHandler(__DIR__.'/sabredav.log', 3, \Monolog\Logger::DEBUG, true, 0600));

/**
 * The directory tree.
 *
 * Basically this is an array which contains the 'top-level' directories in the
 * WebDAV server.
 */
$nodes = [
    // /principals
    new \Sabre\CalDAV\Principal\Collection($principalBackend),
    // /calendars
    new \Sabre\CalDAV\CalendarRoot($principalBackend, $caldavBackend),
    // /addressbook
    new \Sabre\CardDAV\AddressBookRoot($principalBackend, $carddavBackend),
];

// The object tree needs in turn to be passed to the server class
$server = new \Sabre\DAV\Server($nodes);
if (isset($baseUri)) {
    $server->setBaseUri($baseUri);
}

// Logging
$server->setLogger($logger);
//$server->debugExceptions = true; //enable this to include the stacktrace in exception responses

// Plugins
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend));
$server->addPlugin(new \Sabre\DAV\Browser\Plugin());
$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
$server->addPlugin(new \Sabre\DAV\Sharing\Plugin());
$server->addPlugin(new \Sabre\DAVACL\Plugin());

// CalDAV plugins
$server->addPlugin(new \Sabre\CalDAV\Plugin());
$server->addPlugin(new \Sabre\CalDAV\Schedule\Plugin());
$server->addPlugin(new \Sabre\CalDAV\SharingPlugin());
$server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());

// CardDAV plugins
$server->addPlugin(new \Sabre\CardDAV\Plugin());
$server->addPlugin(new \Sabre\CardDAV\VCFExportPlugin());

// And off we go!
$server->start();
