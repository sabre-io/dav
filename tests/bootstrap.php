<?php

declare(strict_types=1);

use Sabre\DAV\Server;
use Sabre\TestUtil;

set_include_path(__DIR__.'/../lib/'.PATH_SEPARATOR.__DIR__.PATH_SEPARATOR.get_include_path());

$autoLoader = include __DIR__.'/../vendor/autoload.php';

// SabreDAV tests auto loading
$autoLoader->add('Sabre\\', __DIR__);
// VObject tests auto loading
$autoLoader->addPsr4('Sabre\\VObject\\', __DIR__.'/../vendor/sabre/vobject/tests/VObject');
$autoLoader->addPsr4('Sabre\\Xml\\', __DIR__.'/../vendor/sabre/xml/tests/Sabre/Xml');

date_default_timezone_set('UTC');

if ('TRUE' === getenv('RUN_TEST_WITH_STREAMING_PROPFIND')) {
    echo 'Running unit tests with \Sabre\DAV\Server::$streamMultiStatus = true';
    Server::$streamMultiStatus = true;
}

if (!file_exists(TestUtil::SABRE_TEMPDIR)) {
    mkdir(TestUtil::SABRE_TEMPDIR);
}
if (file_exists('.sabredav')) {
    unlink('.sabredav');
}
