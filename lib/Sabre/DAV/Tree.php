<?php

/**
 * Abstract tree object 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Tree {
    
    /**
     * This function must return an INode object for a path
     * If a Path doesn't exist, thrown an Exception_FileNotFound
     * 
     * @param string $path 
     * @throws Exception_FileNotFound
     * @return Sabre_DAV_INode 
     */
    abstract function getNodeForPath($path);

    /**
     * Copies a file from path to another
     *
     * @param string $sourcePath The source location
     * @param string $destinationPath The full destination path
     * @return int
     */
    abstract function copy($sourcePath, $destinationPath); 

    /**
     * Moves a file from one location to another 
     * 
     * @param string $sourcePath The path to the file which should be moved 
     * @param string $destinationPath The full destination path, so not just the destination parent node
     * @return int
     */
    abstract function move($sourcePath, $destinationPath);

}

