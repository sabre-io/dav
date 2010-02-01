<?php

class Sabre_DAVACL_Property_AclRestrictions extends Sabre_DAV_Property {

    protected $privileges;

    function serialize(Sabre_DAV_Server $server,DOMElement $elem) {

        $doc = $elem->ownerDocument;

        $elem->appendChild($doc->createElementNS('DAV:','d:grant-only'));
        $elem->appendChild($doc->createElementNS('DAV:','d:no-invert')); 

    }

}

?>
