<?php

/**
 * Base node-class 
 *
 * The node class implements the method used by both the File and the Directory classes 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_FS_Node implements Sabre_DAV_INode {

    /**
     * The path to the current node
     * 
     * @var string 
     */
    protected $path; 

    /**
     * Sets up the node, expects a full path name 
     * 
     * @param string $path 
     * @return void
     */
    public function __construct($path) {

        $this->path = $path;

    }



    /**
     * Returns the name of the node 
     * 
     * @return string 
     */
    public function getName() {

        return basename($this->path);

    }

    /**
     * Renames the node
     *
     * @param string $name The new name
     * @return void
     */
    public function setName($name) {

        rename($this->path,dirname($this->path) . '/' . basename($name));

    }

    /**
     * Returns the size of the node, in bytes 
     * 
     * @return int 
     */
    public function getSize() {
        
        return filesize($this->path);

    }

    /**
     * Returns the last modification time, as a unix timestamp 
     * 
     * @return int 
     */
    public function getLastModified() {

        return filemtime($this->path);

    }

}

