#!/usr/bin/env php
<?php

date_default_timezone_set('UTC');

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

$dependencies = array(
    array(
        'type' => 'php',
        'min'  => '5.3.1',
    ),
    array(
        'type' => 'pearinstaller',
        'min'  => '1.9',
    ),
);


switch($packageName) {

    case 'Sabre' :
        $summary = 'Sabretooth base package.';
        $description = <<<TEXT
The base package provides some functionality used by all packages.

Currently this is only an autoloader
TEXT;
        $version = '1.0.0';
        $stability = 'stable';
        break;

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
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.0.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_HTTP',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.6.0',
        );

        break;

    case 'Sabre_HTTP' :
        $summary = 'Sabre_HTTP provides various HTTP helpers, for input and output and authentication';
        $description = <<<TEXT
Sabre_HTTP effectively wraps around \$_SERVER, php://input, php://output and the headers method,
allowing for a central interface to deal with this as well as easier unittesting.

In addition Sabre_HTTP provides classes for Basic, Digest and Amazon AWS authentication.
TEXT;
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.0.0',
        );
        break;

    case 'Sabre_DAVACL' :
        $summary = 'Sabre_DAVACL provides rfc3744 support.';
        $description = <<<TEXT
Sabre_DAVACL is the RFC3744 implementation for SabreDAV. It provides principals
(users and groups) and access control.
TEXT;
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.0.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_DAV',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.6.0',
        );
        break;

    case 'Sabre_CalDAV' :
        $summary = 'Sabre_CalDAV provides CalDAV extensions to SabreDAV';
        $description = <<<TEXT
Sabre_CalDAV provides RFC4791 (CalDAV) support to Sabre_DAV.

Feature list:
* Multi-user Calendar Server
* Support for Apple iCal, Evolution, Sunbird, Lightning
TEXT;

        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.0.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_HTTP',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.6.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_DAV',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.6.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_DAVACL',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.6.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_VObject',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.3.0',
        );
        break;

    case 'Sabre_CardDAV' :
        $summary = 'Sabre_CardDAV provides CardDAV extensions to SabreDAV';
        $description = <<<TEXT
Sabre_CardDAV provides CardDAV support to Sabre_DAV.

Feature list:
* Multi-user addressbook server
* ACL support
* Support for OS/X, iOS, Evolution and probably more
* Hook-ins for creating a global \'directory\'.
TEXT;

        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.0.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_HTTP',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.6.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_DAV',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.6.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_DAVACL',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.6.0',
        );
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre_VObject',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.3.0',
        );
        break;

    case 'Sabre_VObject' :
        $summary = 'Sabre_VObject is a natural-interface iCalendar and vCard reader';
        $description = <<<TEXT
Sabre_VObject is an intuitive reader for iCalendar and vCard objects.

It provides a natural array/object accessor interface to the parsed tree, much like
simplexml for XML files.
TEXT;
        $dependencies[] = array(
            'type' => 'package',
            'name' => 'Sabre',
            'channel' => 'pear.sabredav.org',
            'min'  => '1.0.0',
        );
        break;

}


if (!isset($version)) {
    include 'lib/' . str_replace('_','/',$packageName) . '/Version.php';
    $versionClassName = $packageName . '_Version';
    $version = $versionClassName::VERSION;
    $stability = $versionClassName::STABILITY;
}

$lead = 'Evert Pot';
$lead_email = 'evert@rooftopsolutions.nl';
$date = date('Y-m-d');

$license = 'Modified BSD';
$licenseuri = 'http://code.google.com/p/sabredav/wiki/License';
$notes = 'New release. Read the ChangeLog and announcement for more details';
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

$fileList  = parsePath($rootDir . '/Sabre', 'php');
$fileList .= parsePath($rootDir . '/examples', 'doc');
$fileList .= parsePath($rootDir . '/ChangeLog', 'doc');
$fileList .= parsePath($rootDir . '/LICENSE', 'doc');

$dependenciesXML = "\n";
foreach($dependencies as $dep) {
    $pad = 8;
    $dependenciesXML.=str_repeat(' ',$pad) . '<' . $dep['type'] . ">\n";
    foreach($dep as $key=>$value) {
        if ($key=='type') continue;
        $dependenciesXML.=str_repeat(' ',$pad+2) . "<$key>$value</$key>\n";
    }
    $dependenciesXML.=str_repeat(' ',$pad) . '</' . $dep['type'] . ">\n";
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
      <email>{$lead_email}</email>
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
      <dir name="/">{$fileList}
      </dir>
    </contents>
    <dependencies>
      <required>{$dependenciesXML}
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
