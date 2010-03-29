<?php

set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/../lib/' . PATH_SEPARATOR . get_include_path());

include 'Sabre.autoload.php';

//Mapping PHP errors to exceptions
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

date_default_timezone_set('UTC');

define("SABRE_TEMPDIR",dirname(__FILE__) . '/temp/');

// If sqlite is not available, this constant is used to skip the relevant
// tests
define('SABRE_HASSQLITE',in_array('sqlite',PDO::getAvailableDrivers()));

if (!file_exists(SABRE_TEMPDIR)) mkdir(SABRE_TEMPDIR);
if (file_exists('.sabredav')) unlink('.sabredav');
