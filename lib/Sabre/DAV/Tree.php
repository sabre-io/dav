<?php

    /**
     * Abstract tree object 
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
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
         * Returns all lock information on a particular path 
         * 
         * This function should return an array with Sabre_DAV_Lock objects. If there are no locks on a file, return an empty array
         *
         * @param string $path 
         * @return array 
         */
        public function getLocks($path) {

            return array();

        }

        /**
         * Locks a path 
         *
         * All the locking information is supplied in the lockInfo object. The object has a suggested timeout, but this can be safely ignored
         * It is important that if the existing timeout is ignored, the property is overwritten, as this needs to be sent back to the client
         * 
         * @param string $path 
         * @param Sabre_DAV_Lock $lockInfo 
         * @return void
         */
        public function lockNode($path,Sabre_DAV_Lock $lockInfo) {


        }

        /**
         * Unlocks a path 
         *
         * This method removes a lock from a path. It is assumed all the correct information is correct and verified
         * 
         * @param string $path 
         * @param Sabre_DAV_Lock $lockInfo 
         * @return void
         */
        public function unlockNode($path,Sabre_DAV_Lock $lockInfo) {


        }

        /**
         * Updates properties
         *
         * This method will receive an array, containing arrays with update information
         * The secondary array will have the following elements:
         *   0 - 1 = set, 2 = remove
         *   1 - the name of the element
         *   2 - the value of the element, represented as a DOMElement 
         * 
         * This method should return a similar array, except it should only return the name of the element and a status code for every mutation. The statuscode should be
         *   200 - if the property was updated
         *   201 - if a new property was created
         *   403 - if changing/deleting the property wasn't allowed
         *   404 - if a non-existent property was attempted to be deleted
         *   or any other applicable HTTP status code
         *
         * The method can also simply return false, if updating properties is not supported
         *
         * @param string $uri the uri for this operation 
         * @param array $mutations 
         * @return void
         */
        public function updateProperties($uri, $mutations) {

            return false;

        }

        /**
         * Returns a list of properties
         *
         * The returned struct should be in the format:
         *
         *   namespace#tagName => contents
         * 
         * @param string $uri The requested uri
         * @param array $properties An array with properties, if its left empty it should return all properties
         * @return void
         */
        public function getProperties($uri,$properties) {
            
            return array();

        }

    }

?>
