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
         * @return int
         */
        abstract function copy($sourcePath, $destinationPath); 

        /**
         * Returns an array with information about nodes 
         * 
         * @param string $path The path to get information about 
         * @param int $depth 0 for just the path, 1 for the path and its children, Sabre_DAV_Server::DEPTH_INFINITY for infinit depth
         * @return array 
         */
        abstract function getNodeInfo($path,$depth = 0);

        /**
         * Deletes a node based on its path 
         * 
         * @param string $path 
         * @return void
         */
        abstract function delete($path);

        /**
         * Updates an existing file node 
         *
         * @param string $path 
         * @param string $data 
         * @return bool
         */
        abstract function put($path, $data);

        /**
         * Creates a new filenode on the specified path
         *
         * @param string $path 
         * @param string $data 
         * @return bool
         */
        abstract function createFile($path, $data);

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
         * @return int
         */
        abstract function move($sourcePath, $destinationPath);

        /**
         * This function should return true or false, depending on wether or not this WebDAV tree supports locking of files 
         *
         * The default is false, if you override this and set it to true, make sure the other lock-related methods are properly implemented
         *
         * @return bool 
         */
        public function supportsLocks() {

            return false;

        }

        /**
         * Returns all lock information on a particular uri 
         * 
         * This function should return an array with Sabre_DAV_Lock objects. If there are no locks on a file, return an empty array
         *
         * @param string $uri 
         * @return array 
         */
        public function getLockInfo($uri) {

            return array();

        }

        /**
         * Locks a uri
         *
         * All the locking information is supplied in the lockInfo object. The object has a suggested timeout, but this can be safely ignored
         * It is important that if the existing timeout is ignored, the property is overwritten, as this needs to be sent back to the client
         * 
         * @param string $uri 
         * @param Sabre_DAV_Lock $lockInfo 
         * @return void
         */
        public function lockNode($uri,Sabre_DAV_Lock $lockInfo) {


        }

        /**
         * Unlocks a uri
         *
         * This method removes a lock from a uri. It is assumed all the correct information is correct and verified
         * 
         * @param string $uri 
         * @param Sabre_DAV_Lock $lockInfo 
         * @return void
         */
        public function unlockNode($uri,Sabre_DAV_Lock $lockInfo) {


        }

    }

?>
