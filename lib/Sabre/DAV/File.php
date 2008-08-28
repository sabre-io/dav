<?php

    require_once 'Sabre/DAV/Exception.php'; 
    require_once 'Sabre/DAV/Node.php';
    require_once 'Sabre/DAV/IFile.php';

    /**
     * File class
     *
     * This is a helper class, that should aid in getting file classes setup.
     * Most of its methods are implemented, and throw permission denied exceptions 
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
     */
    abstract class Sabre_DAV_File extends Sabre_DAV_Node implements Sabre_DAV_IFile {

        /**
         * Updates the data 
         * 
         * @param string $data 
         * @return void 
         */
        public function put($data) { 

            throw new Sabre_DAV_PermissionDeniedException('Permission denied to change data');

        }

        /**
         * Returns the data 
         * 
         * @return string 
         */
        public function get() { 

            throw new Sabre_DAV_PermissionDeniedException('Permission denied to read this file');

        }

    }

?>
