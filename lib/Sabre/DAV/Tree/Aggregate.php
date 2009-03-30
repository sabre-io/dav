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
     * getNodeInfo 
     * 
     * @param mixed $path 
     * @param int $depth 
     * @return void
     */
    function getNodeInfo($path,$depth=0) {

        // We will list contents of the aggregate 
        if (!$path) {
            $items = array();
            $items[] = array(
                'name' => '',
                'type' => Sabre_DAV_Server::NODE_DIRECTORY,
            );

            if ($depth>0) {

                foreach($this->subTrees as $name=>$tree) {

                    list($treeInfo) = $tree->getNodeInfo('',0);
                    $treeInfo['name'] = $name;
                    $items[] = $treeInfo;

                }

            }

            return $items;
        }

        // If we got to this point, it means we need to access a subtree
        $subTree = $this->getSubtree($path);
        if (!$subTree) throw new Sabre_DAV_FileNotFoundException('Subtree with this name not found');

        $nodeInfo = $subTree[0]->getNodeInfo($subTree[1],$depth);
        return $nodeInfo;
        
    }

    /**
     * delete 
     * 
     * @param mixed $path 
     * @return void
     */
    function delete($path) {

        $subTree = $this->getSubTree($path);
        if (!$subTree || !$subTree[1]) throw new Sabre_DAV_PermissionDeniedException();
        return $subTree[0]->delete($subTree[1]);

    }

    /**
     * put 
     * 
     * @param mixed $path 
     * @param mixed $data 
     * @return void
     */
    function put($path,$data) {

        $subTree = $this->getSubTree($path);
        if (!$subTree || !$subTree[1]) throw new Sabre_DAV_PermissionDeniedException();
        return $subTree[0]->put($subTree[1],$data);

    }

    /**
     * createFile 
     * 
     * @param mixed $path 
     * @param mixed $data 
     * @return void
     */
    function createFile($path,$data) {

        $subTree = $this->getSubTree($path);
        if (!$subTree || !$subTree[1]) throw new Sabre_DAV_PermissionDeniedException();
        return $subTree[0]->createFile($subTree[1],$data);

    }

    /**
     * get 
     * 
     * @param mixed $path 
     * @return void
     */
    function get($path) {

        $subTree = $this->getSubTree($path);
        if (!$subTree || !$subTree[1]) throw new Sabre_DAV_PermissionDeniedException();
        return $subTree[0]->get($subTree[1]);

    }

    /**
     * createDirectory 
     * 
     * @param mixed $path 
     * @return void
     */
    function createDirectory($path) {

        $subTree = $this->getSubTree($path);
        if (!$subTree || !$subTree[1]) throw new Sabre_DAV_PermissionDeniedException();
        return $subTree[0]->createDirectory($subTree[1]);

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
        if (!$tree1 || !$tree2) throw new Sabre_DAV_NotImplementedException('Copy not supported in the aggregate root');

        //If they are not within the same tree, we fail as well
        if ($tree1[0]!==$tree2[0]) throw new Sabre_DAV_NotImplementedException('Copy not supported across sub-trees');

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
        if (!$tree1 || !$tree2) throw new Sabre_DAV_NotImplementedException('Copy not supported in the aggregate root');

        //If they are not within the same tree, we fail as well
        if ($tree1[0]!==$tree2[0]) throw new Sabre_DAV_NotImplementedException('Copy not supported across sub-trees');

        return $tree1[0]->move($tree1[1],$tree2[1]);

    }

}
