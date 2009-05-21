<?php

/**
 * ConflictingLock
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id: Exception.php 348 2009-03-26 00:24:28Z evertpot $
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * ConflictingLock 
 *
 * Similar to Exception_Locked, this exception thrown when a LOCK request 
 * was made, on a resource which was already locked
 */
class Sabre_DAV_Exception_ConflictingLock extends Sabre_DAV_Exception_Locked {

    function serialize(DOMElement $errorNode) {
        
        if ($this->lock) {
            $error = $errorNode->ownerDocument->createElementNS('DAV:','d:no-conflicting-lock');
            $errorNode->appendChild($error);
            if (!is_object($this->lock)) var_dump($this->lock);
            $error->appendChild($errorNode->ownerDocument->createElementNS('DAV:','d:href',$this->lock->uri));
        }

    }

}
