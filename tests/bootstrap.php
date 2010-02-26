<?php

set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/../lib/' . PATH_SEPARATOR . get_include_path());

include 'Sabre.autoload.php';

date_default_timezone_set('UTC');

define("SABRE_TEMPDIR",__DIR__ . '/temp');

if (!file_exists(SABRE_TEMPDIR)) mkdir(SABRE_TEMPDIR);
if (file_exists('.sabredav')) unlink('.sabredav');
