<?php

// !!!! Make sure the Sabre directory is in the include_path !!!

// {$Id$} //

// settings
date_default_timezone_set('Canada/Eastern');

// Files we need
require_once 'Sabre.includes.php';

$u = 'admin';
$p = '1234';

$auth = new Sabre_HTTP_DigestAuth();
$auth->init();

if ($auth->getUsername() != $u || !$auth->validatePassword($p)) { 

    $auth->requireLogin();
    echo "Authentication required\n";
    die();

}

?>
