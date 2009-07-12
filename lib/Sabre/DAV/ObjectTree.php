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
    protected $rootNode;


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

            $currentNode = $currentNode->getChild($pathPart); 

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

        } catch (Sabre_DAV_Exception_FileNotFound $e) {

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

            // If the body was a string, we need to convert it to a stream
            if (is_string($data)) {
                $stream = fopen('php://temp','r+');
                fwrite($stream,$data);
                rewind($stream);
                $data = $stream;
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
     * Moves a file from one location to another 
     * 
     * @param string $sourcePath The path to the file which should be moved 
     * @param string $destinationPath The full destination path, so not just the destination parent node
     * @return int
     */
    public function move($sourcePath, $destinationPath) {

        if (dirname($sourcePath)==dirname($destinationPath)) {
            try {
                $destinationNode = $this->getNodeForPath($destinationPath); 
                // If we got here, it means the destination exists, and needs to be overwritten
                $destinationNode->delete();

            } catch (Sabre_DAV_Exception_FileNotFound $e) {

                // If we got here, it means the destination node does not yet exist

            }
            $renameable = $this->getNodeForPath($sourcePath);
            $renameable->setName(basename($destinationPath));
        } else {
            $this->copy($sourcePath,$destinationPath);
            $this->getNodeForPath($sourcePath)->delete();
        }

    }


}

