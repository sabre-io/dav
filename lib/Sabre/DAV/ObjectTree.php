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
     * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
     */
    class Sabre_DAV_ObjectTree extends Sabre_DAV_Tree {

        /**
         * The root node 
         * 
         * @var Sabre_DAV_IDirectory 
         */
        private $rootNode;

        /**
         * Lock manager
         *
         * @var Sabre_DAV_LockManager
         */
        private $lockManager;

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
         * This method allows you to set a global lock manager.
         *
         * Normally locks are handled by nodes implementing Sabre_DAV_ILockable. If this interface is not available, the lock manager can be used as a fallback.
         * 
         * @param Sabre_DAV_LockManager $lockManager 
         * @return void
         */
        public function setLockManager(Sabre_DAV_LockManager $lockManager) {

            $this->lockManager = $lockManager;

        }

        /**
         * Returns the INode object for the requested path  
         * 
         * @param string $path 
         * @return Sabre_DAV_INode 
         */
        public function getNodeForPath($path) {

            $path = trim($path,'/');

            //if (!$path || $path=='.') return $this->rootNode;
            $currentNode = $this->rootNode;
            $i=0;
            // We're splitting up the path variable into folder/subfolder components and traverse to the correct node.. 
            foreach(explode('/',$path) as $pathPart) {

                // If this part of the path is just a dot, it actually means we can skip it
                if ($pathPart=='.' || $pathPart=='') continue;

            //    try { 
                $currentNode = $currentNode->getChild($pathPart); 
            //    } catch (Sabre_DAV_FileNotFoundException $e) { 
            //       throw new Sabre_DAV_FileNotFoundException('we could not find : ' . $path);
            //    }

            }

            return $currentNode;

        }


        /**
         * Copies a file from path to another
         *
         * @param string $sourcePath The source location
         * @param string $destinationPath The full destination path
         * @return int
         */
        public function copy($sourcePath, $destinationPath) {

            $sourceNode = $this->getNodeForPath($sourcePath);
            $destinationParent = $this->getNodeForPath(dirname($destinationPath));

            try {
                $destinationNode = $destinationParent->getChild(basename($destinationPath));

                // If we got here, it means the destination exists, and needs to be overwritten
                $destinationNode->delete();

            } catch (Sabre_DAV_FileNotFoundException $e) {

                // If we got here, it means the destination node does not yet exist

            }

            $this->copyNode($sourceNode,$destinationParent,basename($destinationPath));

        }

        /**
         * copyNode 
         * 
         * @param Sabre_DAV_INode $source 
         * @param Sabre_DAV_IDirectory $destination 
         * @return void
         */
        protected function copyNode(Sabre_DAV_INode $source,Sabre_DAV_IDirectory $destinationParent,$destinationName = null) {

            if (!$destinationName) $destinationName = $source->getName();

            if ($source instanceof Sabre_DAV_IFile) {

                $destinationParent->createFile($destinationName,$source->get());
                $destination = $destinationParent->getChild($destinationName);

            } elseif ($source instanceof Sabre_DAV_IDirectory) {

                $destinationParent->createDirectory($destinationName);
                
                $destination = $destinationParent->getChild($destinationName);
                foreach($source->getChildren() as $child) {

                    $this->copyNode($child,$destination);

                }

            }
            if ($source instanceof Sabre_DAV_IProperties && $destination instanceof Sabre_DAV_IProperties) {

                $props = $source->getProperties(array());
                $newProps = array();
                foreach($props as $k=>$v) {
                    $newProps[] = array(
                        Sabre_DAV_Server::PROP_SET,
                        $k,
                        $v
                    );
                }
                $destination->updateProperties($newProps);

            }

        }

        /**
         * Returns an array with information about nodes 
         * 
         * @param string $path The path to get information about 
         * @param int $depth 0 for just the path, 1 for the path and its children
         * @return array 
         */
        public function getNodeInfo($path,$depth = 0) {

            // The file object
            $fileObject = $this->getNodeForPath($path);

            $fileList = array();

            if ($depth==0 || $depth == Sabre_DAV_Server::DEPTH_INFINITY) {
                $props = array(
                    'name'         => '',
                    'type'         => $fileObject instanceof Sabre_DAV_IDirectory?Sabre_DAV_Server::NODE_DIRECTORY:Sabre_DAV_Server::NODE_FILE,
                    'lastmodified' => $fileObject->getLastModified(),
                    'size'         => $fileObject->getSize(),
                );

                if ($fileObject instanceof Sabre_DAV_IQuota) {

                    $quotaInfo = $fileObject->getQuotaInfo();
                    $props['quota-used'] = $quotaInfo[0];
                    $props['quota-available'] = $quotaInfo[1];

                }

                $fileList[] = $props;

            }

            // If the depth was 1, we'll also want the files in the directory
            if (($depth==1 || $depth==Sabre_DAV_Server::DEPTH_INFINITY) && $fileObject instanceof Sabre_DAV_IDirectory) {

                foreach($fileObject->getChildren() as $child) {
                    $props= array(
                        'name'         => $child->getName(), 
                        'type'         => $child instanceof Sabre_DAV_IDirectory?Sabre_DAV_Server::NODE_DIRECTORY:Sabre_DAV_Server::NODE_FILE,
                        'lastmodified' => $child->getLastModified(),
                        'size'         => $child->getSize(),
                    );

                    if ($child instanceof Sabre_DAV_IQuota) {

                        $quotaInfo = $child->getQuotaInfo();
                        $props['quota-used'] = $quotaInfo[0];
                        $props['quota-available'] = $quotaInfo[1];

                    }

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
         * Creates a new file on the specified path 
         * 
         * @param string $path 
         * @param string $data 
         * @return void
         */
        public function createFile($path,$data) {

            $parent = $this->getNodeForPath(dirname($path));
            return $parent->createFile(basename($path),$data);

        }

        /**
         * Updates an existing file
         * 
         * @param string $path 
         * @param string $data 
         * @return int 
         */
        public function put($path, $data) {

            $node = $this->getNodeForPath($path);
            return $node->put($data);

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

            $parentPath = dirname($path);
            if ($parentPath=='.') $parentPath='/';
            $parent = $this->getNodeForPath($parentPath);
            $parent->createDirectory(basename($path));

        }

        /**
         * Moves a file from one location to another 
         * 
         * @param string $sourcePath The path to the file which should be moved 
         * @param string $destinationPath The full destination path, so not just the destination parent node
         * @return int
         */
        public function move($sourcePath, $destinationPath) {

            $this->copy($sourcePath,$destinationPath);
            $this->delete($sourcePath);

        }

        /**
         * This function should return true or false, depending on wether or not this WebDAV tree supports locking of files 
         *
         * In case of the ObjectTree, this is determined by checking if the root node implements the Sabre_DAV_ILockable interface.
         * If the Root node does not support this interface, we'll simply check if there's a global lock manager
         *
         * @return bool 
         */
        public function supportsLocks() {

            return $this->rootNode instanceof Sabre_DAV_ILockable || $this->lockManager;

        }

        /**
         * Returns all lock information on a particular uri 
         * 
         * This function should return an array with Sabre_DAV_Lock objects. If there are no locks on a file, return an empty array
         *
         * @param string $uri 
         * @return array 
         */
        public function getLocks($uri) {

            try {
                $node = $this->getNodeForPath($uri);
                if ($node instanceof Sabre_DAV_ILockable) return $node->getLocks();
            } catch (Sabre_DAV_FileNotFoundException $e){
                // In case the node didn't exist, this could be a lock-null request
            }
            if ($this->lockManager) return $this->lockManager->getLocks($uri);
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

            try {
                $node = $this->getNodeForPath($uri);
                if ($node instanceof Sabre_DAV_ILockable) return $node->lock($lockInfo);
            } catch (Sabre_DAV_FileNotFoundException $e) {
                // In case the node didn't exist, this could be a lock-null request
            }

            if ($this->lockManager) return $this->lockManager->lock($uri,$lockInfo);

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

            try {
                $node = $this->getNodeForPath($uri);
                if ($node instanceof Sabre_DAV_ILockable) return $node->unlock($lockInfo);
            } catch (Sabre_DAV_FileNotFoundException $e) {
                // In case the node didn't exist, this could be a lock-null request
            }

            if ($this->lockManager) return $this->lockManager->unlock($uri,$lockInfo);

        }

        public function updateProperties($uri,$mutations) {

            $node = $this->getNodeForPath($uri);
            if ($node instanceof Sabre_DAV_IProperties) {
                return $node->updateProperties($mutations);
            } else {
                return false;
            }

        }

        public function getProperties($uri,$properties) {

            $node = $this->getNodeForPath($uri);
            if ($node instanceof Sabre_DAV_IProperties) {
                return $node->getProperties($properties);
            } else {
                return false;
            }

        }

    }

?>
