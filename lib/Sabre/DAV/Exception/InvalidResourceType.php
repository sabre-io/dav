<?php

/**
 * InvalidResourceType 
 *
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */


/**
 * InvalidResourceType 
 *
 * This exception is thrown when the user tried to create a new collection, with
 * a special resourcetype value that was not recognized by the server.
 *
 * See RFC5689 section 3.3
 */
class Sabre_DAV_Exception_InvalidResourceType extends Sabre_DAV_Exception_Forbidden { 

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {

        $error = $errorNode->ownerDocument->createElementNS('DAV:','d:valid-resourcetype');
        $errorNode->appendChild($error);

    }
    
}
