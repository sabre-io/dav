<?php

// {$Id: simplefsserver.php 400 2009-05-21 22:22:02Z evertpot $} //

// !!!! Make sure the Sabre directory is in the include_path !!!
// example:
set_include_path('lib/' . PATH_SEPARATOR . get_include_path()); 

/*

This is the best starting point if you're just interested in setting up a fileserver.

Make sure that the 'public' and 'tmpdata' exists, with write permissions
for your server.

*/

// settings
date_default_timezone_set('Canada/Eastern');
$publicDir = 'public';
$tmpDir = 'tmpdata';

// Make sure this setting is turned on and reflect the root url for your WebDAV server.
// This can be for example the root / or a complete path to your server script
// $baseUri = '/';

if (!isset($baseUri)) die('Please setup \$baseUri first!');

// Files we need
require_once 'Sabre.includes.php';

// Create the parent node
$publicDirObj = new Sabre_DAV_FS_Directory($publicDir);

// Now we create an ObjectTree, which dispatches all requests to your newly created file system
$objectTree = new Sabre_DAV_ObjectTree($publicDirObj);

// Adding support for Lock/Unlock
$lockManager = new Sabre_DAV_LockManager_FS($tmpDir);
$objectTree->setLockManager($lockManager);

// The object tree needs in turn to be passed to the server class
$server = new Sabre_DAV_Server($objectTree);
$server->setBaseUri($baseUri);

// Setting up plugins
$browser = new Sabre_DAV_Browser_Plugin();
$server->addPlugin($browser);

$authBackend = new Sabre_DAV_Auth_Backend_File('.htdigest');
$auth = new Sabre_DAV_Auth_Plugin($authBackend,'SabreDAV');
$server->addPlugin($auth);

// And off we go!
$server->exec();
