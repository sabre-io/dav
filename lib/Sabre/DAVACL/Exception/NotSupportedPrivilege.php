<?php

/**
 * NotSupportedPrivilege
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @version $Id: Locked.php 457 2009-07-12 21:56:12Z evertpot $
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * NotSupportedPrivilege 
 *
 * This exception is thrown if the user tried to set a privilege that was not
 * supported by the server.
 */
class Sabre_DAVACL_Exception_NotSupportedPrivilege extends Sabre_DAV_Exception_PermissionDenied {

    function __construct($privilege) {

        $message = 'The privilege ' . $privilege . ' is not recognized by our system';
        parent::__construct($message);

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {
        
        $doc = $errorNode->ownerDocument;
        
        $np = $doc->createElementNS('DAV:','d:not-supported-privilege');
        $errorNode->appendChild($np);

    }

}

