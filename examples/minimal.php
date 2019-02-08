<?php

/**
 * This example shows the smallest possible sabre/dav server.
 */
include 'vendor/autoload.php';

$server = new Sabre\DAV\Server([
    new Sabre\DAV\FS\Directory(__DIR__),
]);

/*
 * Ok. Perhaps not the smallest possible. The browser plugin is 100% optional,
 * but it really helps understanding the server.
 */
$server->addPlugin(
    new Sabre\DAV\Browser\Plugin()
);

$server->start();
