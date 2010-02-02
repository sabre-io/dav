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
 * NoInvert
 *
 * This exception is thrown if the user tried to set an inverted ACE, but inverted
 * ACE's are not supported.
 */
class Sabre_DAVACL_Exception_NoInvert extends Sabre_DAV_Exception_PermissionDenied {

    protected $uri;
    protected $privileges;

    function __construct($message = null) {

        if(is_null($message)) $message = 'Inverted ace\'s are not supported on this server';
        parent::__construct($message);

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {
        
        $doc = $errorNode->ownerDocument;
        
        $np = $doc->createElementNS('DAV:','d:no-invert');
        $errorNode->appendChild($np);

    }

}

