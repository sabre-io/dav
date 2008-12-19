<?php

    require_once 'Sabre/DAV/INode.php';

    /**
     * This interface represents a file or leaf in the tree.
     *
     * The nature of a file is, as you might be aware of, that it doesn't contain sub-nodes and has contents
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
     */
    interface Sabre_DAV_IFile extends Sabre_DAV_INode {

        /**
         * Updates the data 
         * 
         * @param string $data 
         * @return void 
         */
        function put($data);

        /**
         * Returns the data 
         * 
         * @return string 
         */
        function get();

    }

