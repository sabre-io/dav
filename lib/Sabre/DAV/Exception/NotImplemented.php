<?php

/**
 * NotImplemented
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
 * NotImplemented
 *
 * This exception is thrown when the client tried to call an unsupported HTTP method or other feature
 */
class Sabre_DAV_Exception_NotImplemented extends Sabre_DAV_Exception {

    /**
     * getHTTPCode 
     * 
     * @return int 
     */
    public function getHTTPCode() {
        
        return 501;

    }

}
