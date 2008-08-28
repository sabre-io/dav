<?php

    require_once 'Sabre/DAV/Exception.php'; 
    require_once 'Sabre/DAV/INode.php';

    /**
     * Directory class
     *
     * This is a helper class, that should aid in getting directory classes setup.
     * Most of its methods are implemented, and throw permission denied exceptions 
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
     */
    abstract class Sabre_DAV_Node implements Sabre_DAV_INode {

        /**
         * A default filesize for directories is 0 
         * 
         * @return int
         */
        public function getSize() {

            return 0;

        }

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
         * @throws Sabre_DAV_PermissionDeniedException
         * @return void 
         */
        public function delete() {

            throw new Sabre_DAV_PermissionDeniedException('Permission denied to delete directory');

        }

        /**
         * Renames the node
         * 
         * @throws Sabre_DAV_PermissionDeniedException
         * @param string $name The new name
         * @return void
         */
        public function setName($name) {

            throw new Sabre_DAV_PermissionDeniedException('Permission denied to rename file');

        }

    }

?>
