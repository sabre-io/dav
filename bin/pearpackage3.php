#!/usr/bin/env php
<?php


$make = false;
$packageName = null;

foreach($argv as $index=>$arg) {
    if ($index==0) continue;
    if ($arg=='make') {
        $make = true;
        continue;
    }

    $packageName = $arg;
}

if (is_null($packageName)) {
    echo "A packagename is required\n";
    die(1);
}

if (!is_dir('build/' . $packageName)) {
    echo "Could not find package directory: build/$packageName\n";
    die(2);
}

// We'll figure out something better for this one day

switch($packageName) {
    case 'Sabre_DAV' :
        $summary = 'Sabre_DAV is a WebDAV framework for PHP.';
        $description = <<<TEXT
SabreDAV allows you to easily integrate WebDAV access into your existing PHP application.

Feature List:
* Fully WebDAV (class 1, 2, 3) compliant
* Supports Windows clients, OS/X, DavFS, Cadaver, and pretty much everything we've come accross
* Custom property support
* RFC4918-compliant
* Authentication support
* Plugin system
TEXT;
        break;

    case 'Sabre_HTTP' :
        $summary = 'Sabre_HTTP provides various HTTP helpers, for input and output and authentication';
        $description = <<<TEXT
Sabre_HTTP effectively wraps around \$_SERVER, php://input, php://output and the headers method,
allowing for a central interface to deal with this as well as easier unittesting.

In addition Sabre_HTTP provides classes for Basic, Digest and Amazon AWS authentication.
TEXT;
        break;

}

include 'lib/' . str_replace('_','/',$packageName) . '/Version.php';
$versionClassName = $packageName . '_Version';

$lead = 'Evert Pot';
$lead_email = 'evert@rooftopsolutions.nl';
$date = date('Y-m-d');
$version = $versionClassName::VERSION;
$stability = $versionClassName::STABILITY;
$license = 'Modified BSD';
$licenseuri = 'http://code.google.com/p/sabredav/wiki/License';
$notes = 'New release. Read the ChangeLog and announcement for more details';
$minPHPVersion = '5.2.1';
$channel = 'pear.sabredav.org';

/* This function is intended to generate the full file list */
function parsePath($fullPath, $role, $padding = 4) {

    $fileList = '';
    $file = basename($fullPath);
    if (is_dir($fullPath)) {
        $fileList .= str_repeat(' ', $padding) . "<dir name=\"{$file}\">\n";
        foreach(scandir($fullPath) as $subPath) {;
            if ($subPath==='.' || $subPath==='..') continue;
            $fileList .= parsePath($fullPath. '/' . $subPath,$role, $padding+2);
        }
        $fileList .= str_repeat(' ', $padding) . "</dir><!-- {$file} -->\n"; 
    } elseif (is_file($fullPath)) {
        $fileList .= str_repeat(' ', $padding) . "<file name=\"{$file}\" role=\"{$role}\" />\n";
    }

    return $fileList;

}

$rootDir = realpath('build/' . $packageName);

$fileList  = parsePath($rootDir . '/php', 'php');
$fileList .= parsePath($rootDir . '/doc', 'doc');

// Lastly the install-list
$directory = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDir.'/php'));

$installList = '';
foreach($directory as $path) {
    $basePath = trim(substr($path,strlen($rootDir)),'/');

    // This just takes the 'lib/' off every path name, so it will be installed in the correct location
    $installList .= '        <install name="' . $basePath . '" as="' . substr($basePath,4) . "\" />\n";

}

$package = <<<XML
<?xml version="1.0"?>
<package version="2.0" 
    xmlns="http://pear.php.net/dtd/package-2.0"> 

    <name>{$packageName}</name>
    <channel>{$channel}</channel>
    <summary>{$summary}</summary>
    <description>{$description}</description>
    <lead>
      <name>{$lead}</name>
      <user>{$lead}</user>
      <email>{$lead}</email>
      <active>true</active>
    </lead>
    <date>{$date}</date>
    <version>
      <release>{$version}</release>
      <api>{$version}</api>
    </version>
    <stability>
      <release>{$stability}</release>
      <api>{$stability}</api>
    </stability>
    <license uri="{$licenseuri}">{$license}</license>
    <notes>{$notes}</notes>
    <contents>
      <dir name="/">
{$fileList} 
      </dir>
    </contents>
    <dependencies>
      <required>
        <php><min>{$minPHPVersion}</min></php>
        <pearinstaller><min>1.4</min></pearinstaller>
      </required>
    </dependencies>
    <phprelease />
</package>
XML;
  
if (isset($argv) && in_array('make',$argv)) {
    file_put_contents($rootDir . '/package.xml',$package);
} else {
    echo $package;
}
