<?php

/**
 * Conflict
 *
 * @package Sabre
 * @subpackage DAV
 * @version $Id: Exception.php 348 2009-03-26 00:24:28Z evertpot $
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * Conflict
 *
 * A 409 Conflict is thrown when a user tried to make a directory over an existing
 * file or in a parent directory that doesn't exist.
 */
class Sabre_DAV_Exception_Conflict extends Sabre_DAV_Exception {

    function getHTTPCode() {

        return 409;

    }

}
