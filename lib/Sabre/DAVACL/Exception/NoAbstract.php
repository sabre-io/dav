<?php

/**
 * NoAbstract
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * NoAbstract
 *
 * This exception is thrown if the user tried to set a privilege that was marked as abstract
 */
class Sabre_DAVACL_Exception_NoAbstract extends Sabre_DAV_Exception_PermissionDenied {

    function __construct($privilege) {

        $message = 'The privilege ' . $privilege . ' is abstract';
        parent::__construct($message);

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {
        
        $doc = $errorNode->ownerDocument;
        
        $np = $doc->createElementNS('DAV:','d:no-abstract');
        $errorNode->appendChild($np);

    }

}

