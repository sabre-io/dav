<?php

/**
 * Sabre_DAV_Tree_Aggregate 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Tree_Aggregate extends Sabre_DAV_Tree {

    /**
     * subTrees 
     * 
     * @var array
     */
    private $subTrees = array();

    /**
     * getSubTree 
     * 
     * @param mixed $path 
     * @return void
     */
    protected function getSubTree($path) {

       $parts = explode('/',$path,2);
       
       $subTree = $parts[0];
       $subPath = isset($parts[1])?$parts[1]:'';

       if (isset($this->subTrees[$subTree])) {
           return array($this->subTrees[$subTree],$subPath);
       } else {
           return null;
       }

    }

    /**
     * addTree 
     * 
     * @param mixed $name 
     * @param Sabre_DAV_Tree $tree 
     * @return void
     */
    public function addTree($name,Sabre_DAV_Tree $tree) {

        $this->subTrees[$name] = $tree;

    }

    /**
     * Returns an INode for a given path
     * 
     * @param string $path 
     * @return Sabre_DAV_INode 
     */
    public function getNodeForPath($path) {

        if(!$path) {
            // TODO
            return null;
        }

        // If we got to this point, it means we need to access a subtree
        $subTree = $this->getSubtree($path);
        if (!$subTree) throw new Sabre_DAV_Exception_FileNotFound('Subtree with this name not found');

        $node = $subTree[0]->getNodeForPath($subTree[1]);
        return $node;
    
    }

    /**
     * copy 
     * 
     * @param mixed $sourcePath 
     * @param mixed $destinationPath 
     * @return void
     */
    function copy($sourcePath,$destinationPath) {

        //Obtaining sub-tree's for both paths
        $tree1 = $this->getSubTree($sourcePath);
        $tree2 = $this->getSubTree($destinationPath);
        
        //If either was not in a sub-tree, we fail
        if (!$tree1 || !$tree2) throw new Sabre_DAV_Exception_NotImplemented('Copy not supported in the aggregate root');

        //If they are not within the same tree, we fail as well
        if ($tree1[0]!==$tree2[0]) throw new Sabre_DAV_Exception_NotImplemented('Copy not supported across sub-trees');

        return $tree1[0]->copy($tree1[1],$tree2[1]);

    }

    /**
     * move 
     * 
     * @param mixed $sourcePath 
     * @param mixed $destinationPath 
     * @return void
     */
    function move($sourcePath,$destinationPath) {

        //Obtaining sub-tree's for both paths
        $tree1 = $this->getSubTree($sourcePath);
        $tree2 = $this->getSubTree($destinationPath);
        
        //If either was not in a sub-tree, we fail
        if (!$tree1 || !$tree2) throw new Sabre_DAV_Exception_NotImplemented('Copy not supported in the aggregate root');

        //If they are not within the same tree, we fail as well
        if ($tree1[0]!==$tree2[0]) throw new Sabre_DAV_Exception_NotImplemented('Copy not supported across sub-trees');

        return $tree1[0]->move($tree1[1],$tree2[1]);

    }

}
