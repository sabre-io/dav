<?php

/**
 * PreconditionFailed 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * PreconditionFailed 
 *
 * This exception is normally thrown when a client submitted a conditional request, 
 * like for example an If, If-None-Match or If-Match header, which caused the HTTP 
 * request to not execute (the condition of the header failed)
 */
class Sabre_DAV_Exception_PreconditionFailed extends Sabre_DAV_Exception {

    function getHTTPCode() {

        return 412; 

    }

}
