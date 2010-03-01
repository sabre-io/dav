<?php

include dirname(__FILE__) . '/../lib/Sabre/DAV/Version.php';


$name = 'Sabre_DAV';
$summary = 'SabreDAV is a WebDAV framework for PHP';
$description = <<<TEXT
SabreDAV allows you to easily integrate WebDAV access into your existing PHP application.

Feature List:
* Fully WebDAV (class 1, 2, 3) compliant
* Supports Windows clients, OS/X, DavFS, Cadaver, and pretty much everything we've come accross
* Custom property support
* RFC4918-compliant
* Authentication support
* CalDAV support
* Plugin system
TEXT;

$lead = 'Evert Pot';
$lead_email = 'evert@rooftopsolutions.nl';
$date = date('Y-m-d');
$version = Sabre_DAV_Version::VERSION;
$stability = Sabre_DAV_Version::STABILITY;
$license = 'Modified BSD';
$licenseuri = 'http://code.google.com/p/sabredav/wiki/License';
$notes = 'New release. Read the ChangeLog and announcement for more details';
$minPHPVersion = '5.2.1';


// We are generating 2 types of packages:
// 1. Generated for a uri (direct install)
// 2. Installed from PearFarm
if (isset($argv) && in_array('pearfarm',$argv)) {
    $channel = '<channel>evert.pearfarm.org</channel>';
} else {
    $channel = '<uri>http://sabredav.googlecode.com/files/Sabre_DAV-' . $version . '</uri>';
}


/* This function is intended to generate the full file list */
function parsePath($fullPath, $role, $fileMatch = '/^(.*)$/', $padding = 4) {

    $fileList = '';
    $file = basename($fullPath);
    if (is_dir($fullPath)) {
        $fileList .= str_repeat(' ', $padding) . "<dir name=\"{$file}\">\n";
        foreach(scandir($fullPath) as $subPath) {;
            if ($subPath==='.' || $subPath==='..') continue;
            $fileList .= parsePath($fullPath. '/' . $subPath,$role,$fileMatch, $padding+2);
        }
        $fileList .= str_repeat(' ', $padding) . "</dir><!-- {$file} -->\n"; 
    } elseif (is_file($fullPath)) {
        if (preg_match($fileMatch,$file))
            $fileList .= str_repeat(' ', $padding) . "<file name=\"{$file}\" role=\"{$role}\" />\n";
    }

    return $fileList;

}

$rootDir = realpath(dirname(__FILE__) . '/../');
$fileList  = parsePath($rootDir.'/lib','php','/^(.*)\.php$/');
$fileList .= parsePath($rootDir.'/examples','doc');
$fileList .= parsePath($rootDir.'/tests','test','/^(.*)\.(php|xml)$/');
$fileList .= parsePath($rootDir.'/bin','script','/^(.*)\.py$/');

// Lastly the install-list
$directory = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDir.'/lib'));

$installList = '';
foreach($directory as $path) {

    $basePath = trim(substr($path,strlen($rootDir)),'/');

    // This just takes the 'lib/' off every path name, so it will be installed in the correct location
    $installList .= '        <install name="' . $basePath . '" as="' . substr($basePath,4) . "\" />\n";

}


echo <<<XML
<?xml version="1.0"?>
<package version="2.0" 
    xmlns="http://pear.php.net/dtd/package-2.0"> 

    <name>{$name}</name>
    {$channel}
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
        <pearinstaller><min>1.8</min></pearinstaller>
      </required>
    </dependencies>
    <phprelease>
      <filelist>
        {$installList}
      </filelist>
    </phprelease>
</package>
XML;
  
