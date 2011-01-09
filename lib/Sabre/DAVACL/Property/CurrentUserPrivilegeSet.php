<?php

class Sabre_DAVACL_Property_CurrentUserPrivilegeSet extends Sabre_DAV_Property {

    private $privileges;

    function __construct(array $privileges) {

        $this->privileges = $privileges;

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $node) {

        $doc = $node->ownerDocument;
        foreach($this->privileges as $privName) {

            $this->serializePriv($doc,$node,$privName);

        }

    }

    function serializePriv($doc,$node,$privName) {

        $xp  = $doc->createElementNS('DAV:','d:privilege');
        $node->appendChild($xp);

        $privParts = null;
        preg_match('/^{([^}]*)}(.*)$/',$privName,$privParts);

        $xp->appendChild($doc->createElementNS($privParts[1],'d:'.$privParts[2]));

    }

}
