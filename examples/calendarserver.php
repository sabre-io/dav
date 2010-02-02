<?php

// {$Id: fileserver.php 479 2009-07-22 18:04:27Z evertpot $} //

/*

This is the best starting point if you're just interested in setting up a fileserver.

*/

// settings
date_default_timezone_set('Canada/Eastern');

// Make sure this setting is turned on and reflect the root url for your WebDAV server.
// This can be for example the root / or a complete path to your server scripit
// If this is not setup, we will guess.
// $baseUri = '/';
if (!isset($baseUri)) {
    if (!isset($_SERVER['PATH_INFO'])) throw new Exception('Please setup baseUri!');
    $baseUri = substr($_SERVER['REQUEST_URI'],0,strrpos($_SERVER['REQUEST_URI'],$_SERVER['PATH_INFO'])) . '/';
}

/* Database */
$pdo = new PDO('sqlite:data/db.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

//Mapping PHP errors to exceptions
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

// Files we need
require_once 'lib/Sabre.autoload.php';

// The object tree needs in turn to be passed to the server class
$server = new Sabre_CalDAV_Server($pdo);
$server->setBaseUri($baseUri);

// Support for html frontend
$browser = new Sabre_DAV_Browser_Plugin();
$server->addPlugin($browser);

// And off we go!
$server->exec();
