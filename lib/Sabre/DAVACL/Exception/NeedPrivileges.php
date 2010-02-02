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
 * NeedPrivileges 
 *
 * The 403-need privileges is thrown when a user didn't have the appropriate
 * permissions to perform an operation
 */
class Sabre_DAVACL_Exception_NeedPrivileges extends Sabre_DAV_Exception_PermissionDenied {

    protected $uri;
    protected $privileges;

    function __construct($uri,array $privileges) {

        $this->uri = $uri;
        $this->privileges = $privileges;

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {
        
        $doc = $errorNode->ownerDocument;
        
        $np = $doc->createElementNS('DAV:','d:need-privileges');
        $errorNode->appendChild($np);

        foreach($this->privileges as $privilege) {

            $resource = $doc->createElementNS('DAV:','d:resource');
            $np->appendChild($resource);

            $resource->appendChild($doc->createElementNS('DAV:','d:href',$server->getBaseUri() . $this->uri));

            $priv = $doc->createElementNS('DAV:','d:privilege');
            $resource->appendChild($priv);

            preg_match('/^{([^}]*)}(.*)$/',$privilege,$privilegeParts);
            $priv->appendChild($doc->createElementNS($privilegeParts[1],'d:' . $privilegeParts[2]));


        }

    }

}

