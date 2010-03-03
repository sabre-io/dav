<?php

/**
 * Forbidden
 *
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */


/**
 * Forbidden
 *
 * This exception is thrown whenever a user tries to do an operation that he's not allowed to
 */
class Sabre_DAV_Exception_Forbidden extends Sabre_DAV_Exception {

    /**
     * getHTTPCode 
     * 
     * @return int 
     */
    public function getHTTPCode() {

        return 403;

    }

}
