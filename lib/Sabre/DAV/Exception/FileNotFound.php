<?php

/**
 * FileNotFound
 *
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id: Exception.php 348 2009-03-26 00:24:28Z evertpot $
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */


/**
 * FileNotFound
 *
 * This Exception is thrown when a Node couldn't be found. It returns HTTP error code 404
 */
class Sabre_DAV_Exception_FileNotFound extends Sabre_DAV_Exception {

    /**
     * getHTTPCode 
     * 
     * @return int 
     */
    public function getHTTPCode() {

        return 404;

    }

}

