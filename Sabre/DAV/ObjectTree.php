<?php

    require_once 'Sabre/DAV/Tree.php';
    require_once 'Sabre/DAV/Exception.php';
    require_once 'Sabre/DAV/Server.php';

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
    class Sabre_DAV_ObjectTree extends Sabre_DAV_Tree {

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
         * @param int $depth 0 for just the path, 1 for the path and its children
         * @return array 
         */
        public function getNodeInfo($path,$depth) {

            // The file object
            $fileObject = $this->getNodeForPath($path);

            $props = array(
                'name'         => '',
                'type'         => $fileObject instanceof Sabre_DAV_IDirectory?Sabre_DAV_Server::NODE_DIRECTORY:Sabre_DAV_Server::NODE_FILE,
                'lastmodified' => $fileObject->getLastModified(),
                'size'         => $fileObject->getSize(),
            );

            $fileList[] = $props;

            // If the depth was 1, we'll also want the files in the directory
            if ($depth==1 && $fileObject instanceof Sabre_DAV_IDirectory) {

                foreach($fileObject->getChildren() as $child) {
                    $props= array(
                        'name'         => $child->getName(), 
                        'type'         => $child instanceof Sabre_DAV_IDirectory?Sabre_DAV_Server::NODE_DIRECTORY:Sabre_DAV_Server::NODE_FILE,
                        'lastmodified' => $child->getLastModified(),
                        'size'         => $child->getSize(),
                    );

                    $fileList[] = $props;
                }
                
            }
            return $fileList;

        }

        /**
         * Deletes a node based on its path 
         * 
         * @param string $path 
         * @return void
         */
        public function delete($path) {

            $this->getNodeForPath($path)->delete();

        }

        /**
         * Creates a new file node, or updates an existing one 
         * 
         * @param string $path 
         * @param string $data 
         * @return int 
         */
        public function put($path, $data) {

            $parent = $this->getNodeForPath(dirname($path));
            try {
                $child = $parent->getChild(basename($path));

                // The child existed, so we're updating the contents
                $child->put($data);
                return Sabre_DAV_Server::RESULT_UPDATED;

            } catch (Sabre_DAV_FileNotFoundException $e) {

                // The child didn't exist yet, so we're createing a new one
                $parent->createFile(basename($path),$data);
                return Sabre_DAV_Server::RESULT_CREATED;

            }

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
         * @throws Sabre_DAV_ConflictException This method should return a conflict if the parent directory doesn't exist, or if there's a file with that name on that path 
         * @throws Sabre_DAV_MethodNotAllowedException This method should return this exception when the directory already exists
         * @return void
         */
        public function createDirectory($path) {

            try {

                $parent = $this->getNodeForPath(dirname($path));

                // If the directory was not found, we're actually supposed to throw 409 Conflict
            } catch (Sabre_DAV_FileNotFoundException $e) {

                throw new Sabre_DAV_ConflictException($e->getMessage());

            }

            // Now we'll check if the file already exists
            try {
                $child = $parent->getChild(basename($path));

                // We got so far.. so it already existed. Now for an appropriate error
                if ($child instanceof Sabre_DAV_IDirectory) 

                    // 405 for directories
                    throw new Sabre_DAV_MethodNotAllowedException('Directory already exists');
                else 
                    // 409 for files
                    throw new Sabre_DAV_ConflictException('The file already exists');
                
            } catch (Sabre_DAV_FileNotFoundException $e) {

                // this exception is actually good news
                $parent->createDirectory(basename($path));

            }

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
