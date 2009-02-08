<?php

/**
 * ObjectTree class
 *
 * This implementation of the Tree class makes use of the INode, IFile and IDirectory API's 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
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
     * @return void 
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

            $data = $source->get();
            if (is_string($data)) {
                $data = fopen('data://text/plain,' . $data,'r');
            }
            $destinationParent->createFile($destinationName,$data);
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
     * data is a readable stream resource.
     *
     * @param string $path 
     * @param resource $data 
     * @return void
     */
    public function createFile($path,$data) {

        $parent = $this->getNodeForPath(dirname($path));
        return $parent->createFile(basename($path),$data);

    }

    /**
     * Updates an existing file
     * 
     * data is a readable stream resource.
     *
     * @param string $path 
     * @param resource $data 
     * @return int 
     */
    public function put($path, $data) {

        $node = $this->getNodeForPath($path);
        return $node->put($data);

    }


    /**
     * Returns the contents of a node 
     *
     * This method may either return a string, or a readable stream resource.
     *
     * @param string $path 
     * @return mixed 
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
     * This function should return an array with Sabre_DAV_Lock objects. If there are no locks on a file, return an empty array.
     *
     * Additionally there is also the possibility of locks on parent nodes, so we'll need to traverse every part of the tree 
     *
     * @param string $uri 
     * @return array 
     */
    public function getLocks($uri) {

        $lockList = array();
        $currentPath = '';
        foreach(explode('/',$uri) as $uriPart) {

            $uriLocks = array();
            if ($currentPath) $currentPath.='/'; 
            $currentPath.=$uriPart;

            try {

                $node = $this->getNodeForPath($currentPath);
                if ($node instanceof Sabre_DAV_ILockable) $uriLocks = $node->getLocks();

            } catch (Sabre_DAV_FileNotFoundException $e){
                // In case the node didn't exist, this could be a lock-null request
            }
            if ($this->lockManager) $uriLocks = array_merge($uriLocks,$this->lockManager->getLocks($uri));

            foreach($uriLocks as $uriLock) {

                // Unless we're on the leaf of the uri-tree we should ingore locks with depth 0
                if($uri==$currentPath || $uriLock->depth!=0) {
                    $uriLock->uri = $currentPath;
                    $lockList[] = $uriLock;
                }

            }

        }
        return $lockList;
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
    public function updateProperties($uri,$mutations) {

        $node = $this->getNodeForPath($uri);
        if ($node instanceof Sabre_DAV_IProperties) {
            return $node->updateProperties($mutations);
        } else {
            return false;
        }

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

        $node = $this->getNodeForPath($uri);
        if ($node instanceof Sabre_DAV_IProperties) {
            return $node->getProperties($properties);
        } else {
            return false;
        }

    }

}

