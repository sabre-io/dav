<?php

/**
 * NeedPrivileges 
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @version $Id: Locked.php 457 2009-07-12 21:56:12Z evertpot $
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * AllowedPrincipal 
 *
 * This exception is thrown, if the user tried to set an ACE with a principal that is
 * not allowed.
 */
class Sabre_DAVACL_Exception_AllowedPrincipal extends Sabre_DAV_Exception_PermissionDenied {

    function __construct($message = null) {

        if(is_null($message)) $message = 'The principal you tried to use is not supported by this server.';
        parent::__construct($message);

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {
        
        $doc = $errorNode->ownerDocument;
        
        $np = $doc->createElementNS('DAV:','d:allowed-principal');
        $errorNode->appendChild($np);

    }

}

