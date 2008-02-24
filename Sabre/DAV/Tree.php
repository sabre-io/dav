<?php

    /**
     * Abstract tree object 
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license license http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
     */
    abstract class Sabre_DAV_Tree {

        /**
         * Copies a file from path to another
         *
         * @param string $sourcePath The source location
         * @param string $destinationPath The full destination path
         * @param int $depth How deep the copy should be done
         * @param bool $overwrite Wether or not to overwrite the destniation location
         * @return int
         */
        abstract function copy($sourcePath, $destinationPath, $depth, $overwrite); 

        /**
         * Returns an array with information about nodes 
         * 
         * @param string $path The path to get information about 
         * @param int $depth 0 for just the path, 1 for the path and its children, Sabre_DAV_Server::DEPTH_INFINITY for infinit depth
         * @return array 
         */
        abstract function getNodeInfo($path,$depth);

        /**
         * Deletes a node based on its path 
         * 
         * @param string $path 
         * @return void
         */
        abstract function delete($path);

        /**
         * Creates a new file node, or updates an existing one 
         *
         * This method MUST return either Sabre_DAV_Server::RESULT_CREATED or Sabre_DAV_Server::RESULT_UPDATED
         *
         * @param string $path 
         * @param string $data 
         * @return int 
         */
        abstract function put($path, $data);

        /**
         * Returns the contents of a node 
         * 
         * @param string $path 
         * @return string 
         */
        abstract function get($path);

        /**
         * Creates a new directory 
         * 
         * @param string $path The full path to the new directory 
         * @throws Sabre_DAV_ConflictException This method should return a conflict if the parent directory doesn't exist, or if there's a file with that name on that path 
         * @throws Sabre_DAV_MethodNotAllowedException This method should return this exception when the directory already exists
         * @return void
         */
        abstract function createDirectory($path);

        /**
         * Moves a file from one location to another 
         * 
         * @param string $sourcePath The path to the file which should be moved 
         * @param string $destinationPath The full destination path, so not just the destination parent node
         * @param bool $overwrite Whether or not to overwrite the destiniation  
         * @return int
         */
        abstract function move($sourcePath, $destinationPath, $overwrite);

    }

?>
