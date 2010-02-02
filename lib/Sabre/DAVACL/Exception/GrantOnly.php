<?php

/**
 * NeedPrivileges 
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * GrantOnly 
 *
 * This exception is thrown if the user tried to set a deny rule, but only grants are supported
 */
class Sabre_DAVACL_Exception_GrantOnly extends Sabre_DAV_Exception_PermissionDenied {

    protected $uri;
    protected $privileges;

    function __construct($message = null) {

        if(is_null($message)) $message = 'Deny ace\'s are not supported on this server';
        parent::__construct($message);

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {
        
        $doc = $errorNode->ownerDocument;
        
        $np = $doc->createElementNS('DAV:','d:grant-only');
        $errorNode->appendChild($np);

    }

}

