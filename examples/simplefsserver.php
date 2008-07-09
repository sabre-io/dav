<?php

// !!!! Make sure the Sabre directory is in the include_path !!!

// {$Id} //

// settings
date_default_timezone_set('Canada/Eastern');
$publicDir = 'public';
$baseUri = '/';

// Files we need

require_once 'Sabre/DAV/Server.php';
require_once 'Sabre/DAV/ObjectTree.php';
require_once 'Sabre/DAV/LockManager/FS.php';
require_once 'Sabre/DAV/Directory.php';
require_once 'Sabre/DAV/File.php';

class MyDirectory extends Sabre_DAV_Directory {

  private $myPath;

  function __construct($myPath) {

    $this->myPath = $myPath;

  }

  function getChildren() {

    $children = array();
    // Loop through the directory, and create objects for each node
    foreach(scandir($this->myPath) as $node) {

      // Ignoring files staring with .
      if ($node[0]==='.') continue;

      $children[] = $this->getChild($node);

    }

    return $children;

  }

    function getChild($name) {

        $path = $this->myPath . '/' . $name;

        // We have to throw a FileNotFoundException if the file didn't exist
        if (!file_exists($this->myPath)) throw new Sabre_DAV_FileNotFoundException('The file with name: ' . $name . ' could not be found');
        // Some added security

        if ($name[0]=='.')  throw new Sabre_DAV_FileNotFoundException('Access denied');

        if (is_dir($path)) {

            return new MyDirectory($name);

        } else {

            return new MyFile($path);

        }

    }

    function getName() {

        return basename($this->myPath);

    }

}

class MyFile extends Sabre_DAV_File {

  private $myPath;

  function __construct($myPath) {

    $this->myPath = $myPath;

  }

  function getName() {

      return basename($this->myPath);

  }

  function get() {

    return file_get_contents($this->myPath);

  }

  function getSize() {

      return filesize($this->myPath);

  }

}

// Make sure there is a directory in your current directory named 'public'. We will be exposing that directory to WebDAV
$rootDir = new MyDirectory($publicDir);

// Now we create an ObjectTree, which dispatches all requests to your newly created file system
$objectTree = new Sabre_DAV_ObjectTree($rootDir);

// The object tree needs in turn to be passed to the server class
$server = new Sabre_DAV_Server($objectTree);

$server->setBaseUri($baseUri);

// And off we go!
$server->exec();

?>
