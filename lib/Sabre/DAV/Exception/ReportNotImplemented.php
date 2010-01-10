<?php

/**
 * ReportNotImplemented
 *
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * ReportNotImplemented
 *
 * This exception is thrown when the client requested an unknown report through the REPORT method
 */
class Sabre_DAV_Exception_ReportNotImplemented extends Sabre_DAV_Exception_NotImplemented {

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {

        $error = $errorNode->ownerDocument->createElementNS('DAV:','d:supported-report');
        $errorNode->appendChild($error);

    }

}
