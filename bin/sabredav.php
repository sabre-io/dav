<?php

// SabreDAV test server.

class CliLog {

    protected $stream;

    function __construct() {

        $this->stream = fopen('php://stdout','w');

    }

    function log($msg) {
        fwrite($this->stream, $msg . "\n");
    }

}

$log = new CliLog();

if (php_sapi_name()!=='cli-server') {
    die("This script is intended to run on the built-in php webserver");
}

// Finding composer


$paths = array(
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
);

foreach($paths as $path) {
    if (file_exists($path)) {
        include $path;
    }
}

// Root 
$root = new Sabre_DAV_FS_Directory(getcwd());

// Setting up server.
$server = new Sabre_DAV_Server($root);

// Browser plugin
$server->addPlugin(new Sabre_DAV_Browser_Plugin());

$server->exec();
