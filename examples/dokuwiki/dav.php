<?php
/**
 * WebDAV endpoint  
 * 
 * @copyright Copyright (C) 2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

  if(!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/');
  require_once(DOKU_INC.'inc/init.php');
  require_once(DOKU_INC.'inc/common.php');
  require_once(DOKU_INC.'inc/events.php');
  require_once(DOKU_INC.'inc/parserutils.php');
  require_once(DOKU_INC.'inc/auth.php');
  require_once(DOKU_INC.'inc/pageutils.php');
  require_once(DOKU_INC.'inc/fulltext.php');


  // Sabre_DAV needs to be in the include path, in order to work..
  set_include_path(get_include_path() . PATH_SEPARATOR . 'lib/');

  //close session
  session_write_close();

  require_once 'Sabre/DAV/Directory.php';
  require_once 'Sabre/DAV/File.php';
  require_once 'Sabre/DAV/Server.php';
  require_once 'Sabre/DAV/ObjectTree.php';

  /* Prepare the page list */



  class DokuWiki_DAV_Directory extends Sabre_DAV_Directory {

    private $namespace;

    public function __construct($namespace) {

        $this->namespace = $namespace;

    }

    public function getName() {

        $parts = explode(':',$this->namespace);
        return $parts[count($parts)-1];

    }

    public function getChildren() {

        $pages = ft_pageLookup($this->namespace,false);

        $filteredPages = array();
        $namespacePrefix = $this->namespace . ($this->namespace?':':'');

        $children = array();
    
        // Specifically, we are looking for all pages that start with the current namespace
        foreach($pages as $page) {

            // We have a match
            if (!$namespacePrefix || ( strpos($page,$namespacePrefix)===0 && $page!=$namespacePrefix ) ) {

                // The full name of the page under the current namespace
                $childName = substr($page,strlen($namespacePrefix));

                $childParts = explode(':',$childName);

                // If there was more than 1 part, the page in the list was part of a sub-namespace, otherwise it wasn't :)
                if(count($childParts)>1) {
                    
                    $children[$namespacePrefix.$childParts[0]] = new DokuWiki_DAV_Directory($namespacePrefix.$childParts[0]);

                } else {

                    $children[$namespacePrefix.$childParts[0].'.txt'] = new DokuWiki_DAV_File($namespacePrefix.$childParts[0]);

                }

            }

        }

        // We need to get rid of the keys
        return array_values($children); 

    }

    public function getChild($name) {

        list($name) = explode('.',$name);
   
        // This version of getChild strips out any extensions
        foreach($this->getChildren() as $child) {

            $childName = $child->getName();
            list($childName) = explode('.',$childName);
            if ($childName==$name) return $child;

        }
        throw new Sabre_DAV_Exception_FileNotFound('File not found: ' . $name);

    }

  }

  class DokuWiki_DAV_File extends Sabre_DAV_File {

    private $path;

    public function __construct($path) {

        $this->path = $path;

    }

    public function getName() {

        $parts = explode(':',$this->path);
        return $parts[count($parts)-1] . '.txt';

    }

    public function get() {

        if(auth_quickaclcheck($this->path) < AUTH_READ){
            throw new Sabre_DAV_Exception_PermissionDenied('You are not allowed to view this page');
        }
        return rawWiki($this->path,'');

    }

    public function put($text) {

        global $TEXT;
        global $lang;

        $id    = cleanID($this->path);
        $TEXT  = trim($text);
        $sum   = '';
        $minor = '';

        if(auth_quickaclcheck($id) < AUTH_EDIT)
            throw new Sabre_DAV_Exception_PermissionDenied('You are not allowed to edit this page');

        // Check, if page is locked
        if(checklock($id))
            return new Sabre_DAV_Exception_PermissionDenied('The page is currently locked');

        // SPAM check
        if(checkwordblock()) 
            return new Sabre_DAV_Exception_PermissionDenied('Positive wordblock check');

        // autoset summary on new pages
        if(!page_exists($id) && empty($sum)) {
            $sum = $lang['created'];
        }

        // autoset summary on deleted pages
        if(page_exists($id) && empty($TEXT) && empty($sum)) {
            $sum = $lang['deleted'];
        }

        lock($id);
        saveWikiText($id,$TEXT,$sum,$minor);
        unlock($id);

    }

  }


$objectTree = new Sabre_DAV_ObjectTree(new DokuWiki_DAV_Directory(''));
$server = new Sabre_DAV_Server($objectTree);
$server->setBaseUri(getBaseURL() . 'dav.php');
$server->exec();

//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
