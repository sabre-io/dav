<?php

set_include_path('../lib/' . PATH_SEPARATOR . get_include_path());

include 'Sabre.includes.php';

date_default_timezone_set('UTC');

if (!file_exists('temp')) mkdir('temp');

