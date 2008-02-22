<?php

    /**
     * The INode interface is the base interface, and the parent class of both IDirectory and IFile
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
     */
    interface Sabre_DAV_INode {

        /**
         * Deleted the current node
         *
         * @return void 
         */
        function unlink();

        /**
         * Returns the name of the node 
         * 
         * @return string 
         */
        function getName();

        /**
         * Renames the node
         * 
         * @return void
         */
        function setName();

        /**
         * Returns the size of the node, in bytes 
         * 
         * @return int 
         */
        function getSize();

        /**
         * Returns the last modification time, as a unix timestamp 
         * 
         * @return int 
         */
        function getLastModified();

    }

?>
