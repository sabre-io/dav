<?php

/**
 * Node class
 *
 * This is a helper class, that should aid in getting nodes setup. 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Node implements Sabre_DAV_INode {

    /**
     * Returns the last modification time 
     *
     * In this case, it will simply return the current time
     *
     * @return int 
     */
    public function getLastModified() {

        return time();

    }

    /**
     * Deleted the current node
     *
     * @throws Sabre_DAV_Exception_PermissionDenied
     * @return void 
     */
    public function delete() {

        throw new Sabre_DAV_Exception_PermissionDenied('Permission denied to delete node');

    }

    /**
     * Renames the node
     * 
     * @throws Sabre_DAV_Exception_PermissionDenied
     * @param string $name The new name
     * @return void
     */
    public function setName($name) {

        throw new Sabre_DAV_Exception_PermissionDenied('Permission denied to rename file');

    }

}

