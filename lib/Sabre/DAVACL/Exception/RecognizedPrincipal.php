<?php

/**
 * RecognizedPrincipal 
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * RecognizedPrincipal 
 *
 * This exception is thrown, if the user tried to set an ACE with a non-existant
 * principal.
 */
class Sabre_DAVACL_Exception_RecognizedPrincipal extends Sabre_DAV_Exception_PermissionDenied {

    function __construct($principal = null) {

        $message = 'The principal ' . $principal . ' could not be found in our system';
        parent::__construct($message);

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {
        
        $doc = $errorNode->ownerDocument;
        
        $np = $doc->createElementNS('DAV:','d:recognized-principal');
        $errorNode->appendChild($np);

    }

}

