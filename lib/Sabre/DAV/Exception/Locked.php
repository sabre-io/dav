<?php

/**
 * Locked
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * Locked 
 *
 * The 423 is thrown when a client tried to access a resource that was locked, without supplying a valid lock token
 */
class Sabre_DAV_Exception_Locked extends Sabre_DAV_Exception {

    protected $lock;

    function __construct($lock = null) {

        $this->lock = $lock;

    }
    function getHTTPCode() {

        return 423;

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {
        
        if ($this->lock) {
            $error = $errorNode->ownerDocument->createElementNS('DAV:','d:lock-token-submitted');
            $errorNode->appendChild($error);
            if (!is_object($this->lock)) var_dump($this->lock);
            $error->appendChild($errorNode->ownerDocument->createElementNS('DAV:','d:href',$this->lock->uri));
        }

    }

}

