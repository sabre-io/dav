<?php

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
require_once 'Sabre/autoload.php';

// Create the parent node
$publicDirObj = new Sabre_DAV_FS_Directory($publicDir);

// Now we create an ObjectTree, which dispatches all requests to your newly created file system
$objectTree = new Sabre_DAV_ObjectTree($publicDirObj);

// The object tree needs in turn to be passed to the server class
$server = new Sabre_DAV_Server($objectTree);
$server->setBaseUri($baseUri);

// Support for LOCK and UNLOCK 
$lockBackend = new Sabre_DAV_Locks_Backend_FS($tmpDir);
$lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
$server->addPlugin($lockPlugin);

// Support for html frontend
$browser = new Sabre_DAV_Browser_Plugin();
$server->addPlugin($browser);

// Authentication backend
$authBackend = new Sabre_DAV_Auth_Backend_File('.htdigest');
$auth = new Sabre_DAV_Auth_Plugin($authBackend,'SabreDAV');
$server->addPlugin($auth);

// Temporary file filter
$tempFF = new Sabre_DAV_TemporaryFileFilterPlugin($tmpDir);
$server->addPlugin($tempFF);

// And off we go!
$server->exec();
