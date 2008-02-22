<?php

    require_once 'Sabre/DAV/Tree.php';
    require_once 'Sabre/DAV/Exception.php';

    /**
     * ObjectTree class
     *
     * This implementation of the Tree class makes use of the INode, IFile and IDirectory API's 
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license license http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
     */
    class Sabre_DAV_ObjectTree {

        /**
         * The root node 
         * 
         * @var Sabre_DAV_IDirectory 
         */
        private $rootNode;

        /**
         * Creates the object
         *
         * This method expects the rootObject to be passed as a parameter
         * 
         * @param Sabre_DAV_IDirectory $rootNode 
         * @return void
         */
        public function __construct(Sabre_DAV_IDirectory $rootNode) {

            $this->rootNode = $rootNode;

        }

        /**
         * Returns the INode object for the requested path  
         * 
         * @param string $path 
         * @return Sabre_DAV_INode 
         */
        public function getNodeForPath($path) {

            $path = trim($path,'/');

            if (!$path) return $this->rootNode;

            $currentNode = $this->rootNode;

            foreach(explode('/',$path) as $pathPart) {

                $currentNode = $currentNode->getChild($pathPart); 

            }

            return $currentNode;

        }


        /**
         * Copies a file from path to another
         *
         * @param string $sourcePath The source location
         * @param string $destinationPath The full destination path
         * @param int $depth How deep the copy should be done
         * @param bool $overwrite Wether or not to overwrite the destniation location
         * @return int
         */
        public function copy($sourcePath, $destinationPath, $depth, $overwrite) {

            throw new Sabre_DAV_MethodNotImplementedException('Copy is not yet implemented');

        }

        /**
         * Returns an array with information about nodes 
         * 
         * @param string $path The path to get information about 
         * @param int $depth 0 for just the path, 1 for the path and its children, Sabre_DAV_Server::DEPTH_INFINITY for infinit depth
         * @return array 
         */
        public function getNodeInfo($path,$depth) {

            throw new Sabre_DAV_MethodNotImplementedException('getNodeInfo is not yet implemented');

        }

        /**
         * Deletes a node based on its path 
         * 
         * @param string $path 
         * @return void
         */
        public function delete($path) {

            throw new Sabre_DAV_MethodNotImplementedException('delete is not yet implemented');

        }

        /**
         * Creates a new file node, or updates an existing one 
         * 
         * @param string $path 
         * @param string $data 
         * @return int 
         */
        public function put($path, $data) {

            throw new Sabre_DAV_MethodNotImplementedException('put is not yet implemented');

        }


        /**
         * Returns the contents of a node 
         * 
         * @param string $path 
         * @return string 
         */
        public function get($path) {

            return $this->getNodeForPath($path)->get();

        }

        /**
         * Creates a new directory 
         * 
         * @param string $path The full path to the new directory 
         * @return void
         */
        public function createDirectory($path) {

            throw new Sabre_DAV_MethodNotImplementedException('createDirectory is not yet implemented');

        }

        /**
         * Moves a file from one location to another 
         * 
         * @param string $sourcePath The path to the file which should be moved 
         * @param string $destinationPath The full destination path, so not just the destination parent node
         * @param bool $overwrite Whether or not to overwrite the destiniation  
         * @return int
         */
        public function move($sourcePath, $destinationPath, $overwrite) {

            throw new Sabre_DAV_MethodNotImplementedException('move is not yet implemented');

        }

    }

?>
