#!/usr/bin/env php
<?php

/**
 * PEAR package.xml generator.
 *
 * This file contains all includes to the rest of the SabreDAV library
 * Make sure the lib/ directory is in PHP's include_path
 *
 * @package Sabre
 * @subpackage DAV
 * @version $Id: Sabre.includes.php 489 2009-07-28 19:02:28Z evertpot $
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @author Michael Gauthier
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
require_once 'PEAR/PackageFileManager2.php';
require_once dirname(__FILE__) . '/../lib/Sabre/DAV/Version.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$apiVersion     = Sabre_DAV_Version::VERSION;
$apiState       = Sabre_DAV_Version::STABILITY;

$releaseVersion = Sabre_DAV_Version::VERSION; 
$releaseState   = Sabre_DAV_Version::STABILITY; 

$description = <<<TEXT
    SabreDAV allows you to easily integrate your existing web application
    with WebDAV.

    Feature list:

     * Fully WebDAV compliant
     * Supports Windows XP, Windows Vista, Mac OS/X, DavFSv2, Cadaver,
       Netdrive.
     * Passing all Litmus tests
     * Supporting class 1, 2 and 3 webdav servers
     * Locking support
     * Custom property support
     * Supports: RFC2518 and revisions from RFC4918
     * Has built-in support for (basic/digest) authentication (RFC2617)
TEXT;

$package = new PEAR_PackageFileManager2();

$package->setOptions(
    array(
        'filelistgenerator'          => 'svn',
        'simpleoutput'               => true,
        'baseinstalldir'             => '/',
        'packagedirectory'           => './',
        'dir_roles'                  => array(
            'bin'                    => 'script',
//            'docs'                   => 'doc',
            'examples'               => 'doc',
            'lib/Sabre'              => 'php',
            'lib/Sabre.autoload.php' => 'php',
            'lib/Sabre.includes.php' => 'php',
            'tests'                  => 'test'
        ),
        'exceptions'                 => array(
            'ChangeLog'              => 'doc',
            'LICENCE'                => 'doc',
        ),
        'ignore'                     => array(
            'build.xml',
            'bin/*',
            'docs/*',
        )
    )
);

$package->setPackage('Sabre_DAV');
$package->setSummary(
    'SabreDAV allows you to easily integrate your existing web ' .
    'application with WebDAV'
);
$package->setDescription($description);
$package->setUri('http://sabredav.googlecode.com/files/Sabre_DAV-' . $releaseVersion);
$package->setPackageType('php');
$package->setLicense('BSD', 'http://code.google.com/p/sabredav/wiki/License');

$package->setNotes('Maintance release. See ChangeLog for more details.');
$package->setReleaseVersion($releaseVersion);
$package->setReleaseStability($releaseState);
$package->setAPIVersion($apiVersion);
$package->setAPIStability($apiState);

$package->addMaintainer(
    'lead',
    'evert',
    'Evert Pot',
    'http://www.rooftopsolutions.nl/'
);

$package->addExtensionDep('required', 'dom');
$package->addExtensionDep('required', 'xmlwriter');
$package->setPhpDep('5.2.1');
$package->setPearinstallerDep('1.4.0');
$package->generateContents();

$package->addRelease();

/*
 * Files get installed without the lib/ directory so they fit in PEAR's
 * file naming scheme.
 */
function getDirectory($path)
{
    $files = array();

    $ignore = array('.', '..', '.svn','.DS_Store');

    $d = opendir($path);

    while (false !== ($file = readdir($d))) {
        $newPath = $path . '/' . $file;
        if (!in_array($file, $ignore)) {
            if (is_dir($newPath)) {
                $files = array_merge($files, getDirectory($newPath));
            } else {
                echo 'Including: ', $newPath, "\n";
                $files[] = $newPath;
            }
        }
    }

    closedir($d);
    return $files;
}
$files = getDirectory('lib');
foreach ($files as $file) {
    // strip off 'lib/' dir
    $package->addInstallAs($file, substr($file, 4));
}

if (isset($_GET['make'])
    || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')
) {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}

?>
