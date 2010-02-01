<?php

class Sabre_CalDAV_Property_SupportedCollationSet extends Sabre_DAV_Property {

    function serialize(Sabre_DAV_Server $server,DOMElement $node) {

        $doc = $node->ownerDocument;
        
        $prefix = $node->lookupPrefix('urn:ietf:params:xml:ns:caldav');
        if (!$prefix) $prefix = 'cal';

        $node->appendChild(
            $doc->createElement($prefix . ':supported-collation','i;ascii-casemap')
        );
        $node->appendChild(
            $doc->createElement($prefix . ':supported-collation','i;octet')
        );


    }

}
