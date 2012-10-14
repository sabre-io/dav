<?php

/*

CalendarServer example

This server features CalDAV support

*/

// settings
date_default_timezone_set('Canada/Eastern');

// If you want to run the SabreDAV server in a custom location (using mod_rewrite for instance)
// You can override the baseUri here.
// $baseUri = '/';

/* Database */
$pdo = new PDO('sqlite:data/db.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

//Mapping PHP errors to exceptions
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

// Files we need
require_once 'vendor/autoload.php';

// Backends
$authBackend = new Sabre_DAV_Auth_Backend_PDO($pdo);
$calendarBackend = new Sabre_CalDAV_Backend_PDO($pdo);
$principalBackend = new Sabre_DAVACL_PrincipalBackend_PDO($pdo);

// Directory structure 
$tree = array(
    new Sabre_CalDAV_Principal_Collection($principalBackend),
    new Sabre_CalDAV_CalendarRootNode($principalBackend, $calendarBackend),
);

$server = new Sabre_DAV_Server($tree);

if (isset($baseUri))
    $server->setBaseUri($baseUri);

/* Server Plugins */
$authPlugin = new Sabre_DAV_Auth_Plugin($authBackend,'SabreDAV');
$server->addPlugin($authPlugin);

$aclPlugin = new Sabre_DAVACL_Plugin();
$server->addPlugin($aclPlugin);

$caldavPlugin = new Sabre_CalDAV_Plugin();
$server->addPlugin($caldavPlugin);

// Support for html frontend
$browser = new Sabre_DAV_Browser_Plugin();
$server->addPlugin($browser);

// And off we go!
$server->exec();
