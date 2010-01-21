<?php

set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/../lib/' . PATH_SEPARATOR . get_include_path());

include 'Sabre.autoload.php';

date_default_timezone_set('UTC');

if (!file_exists('temp')) mkdir('temp');

