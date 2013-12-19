<?php

set_include_path(__DIR__ . '/../lib/' . PATH_SEPARATOR . __DIR__ . PATH_SEPARATOR . get_include_path());

include __DIR__ . '/../vendor/autoload.php';
include 'Sabre/TestUtil.php';
include 'Sabre/DAVServerTest.php';
include 'Sabre/CardDAV/Backend/AbstractPDOTest.php';
include 'Sabre/CardDAV/TestUtil.php';

date_default_timezone_set('GMT');

$config = [
    'SABRE_TEMPDIR'   => dirname(__FILE__) . '/temp/',
    'SABRE_HASSQLITE' => in_array('sqlite',PDO::getAvailableDrivers()),
    'SABRE_HASMYSQL'  => in_array('mysql',PDO::getAvailableDrivers()),
    'SABRE_MYSQLDSN'  => 'mysql:host=127.0.0.1;dbname=sabredav',
    'SABRE_MYSQLUSER' => 'root',
    'SABRE_MYSQLPASS' => '',
];

foreach($config as $key=>$value) {
    if (!defined($key)) define($key, $value);
}

if (!file_exists(SABRE_TEMPDIR)) mkdir(SABRE_TEMPDIR);
if (file_exists('.sabredav')) unlink('.sabredav');
