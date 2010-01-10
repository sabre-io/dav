<?php

/**
 * UnsupportedMediaType
 *
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * UnSupportedMediaType
 *
 * The 415 Unsupported Media Type status code is generally sent back when the client 
 * tried to call an HTTP method, with a body the server didn't understand
 */
class Sabre_DAV_Exception_UnsupportedMediaType extends Sabre_DAV_Exception { 

    function getHTTPCode() {

        return 415;

    }

}
