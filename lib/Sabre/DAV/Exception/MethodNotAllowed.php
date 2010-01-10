<?php

/**
 * MethodNotAllowed
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * MethodNotAllowed
 *
 * The 405 is thrown when a client tried to create a directory on an already existing directory
 */
class Sabre_DAV_Exception_MethodNotAllowed extends Sabre_DAV_Exception {

    function getHTTPCode() {

        return 405;

    }

}
