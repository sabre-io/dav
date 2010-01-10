<?php

/**
 * SabreDAV base exception
 *
 * This is SabreDAV's base exception file, use this to implement your own exception.
 *
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * Main Exception class. 
 *
 * This class defines a getHTTPCode method, which should return the appropriate HTTP code for the Exception occured.
 * The default for this is 500.
 *
 * This class also allows you to generate custom xml data for your exceptions. This will be displayed
 * in the 'error' element in the failing response.
 */
class Sabre_DAV_Exception extends Exception { 

    /**
     * getHTTPCode
     *
     * @return int
     */
    public function getHTTPCode() { 

        return 500;

    }

    /**
     * This method allows the exception to include additonal information into the WebDAV error response 
     * 
     * @param DOMElement $errorNode 
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {
    

    }

}

