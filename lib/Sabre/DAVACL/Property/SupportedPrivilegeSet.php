<?php

class Sabre_DAVACL_Property_SupportedPrivilegeSet extends Sabre_DAV_Property {

    private $privileges;

    function __construct(array $privileges) {

        $this->privileges = $privileges;

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $node) {

        $doc = $node->ownerDocument;
        foreach($this->privileges as $privName=>$privInfo) {

            $this->serializePriv($doc,$node,$privName,$privInfo);

        }

    }

    function serializePriv($doc,$node,$privName,$privInfo) {

        $xsp = $doc->createElementNS('DAV:','d:supported-privilege');
        $node->appendChild($xsp);

        $xp  = $doc->createElementNS('DAV:','d:privilege');
        $xsp->appendChild($xp);

        $privParts = null;
        preg_match('/^{([^}]*)}(.*)$/',$privName,$privParts);

        $xp->appendChild($doc->createElementNS($privParts[1],'d:'.$privParts[2]));

        if (isset($privInfo['abstract']) && $privInfo['abstract']) {
            $xsp->appendChild($doc->createElementNS('DAV:','d:abstract'));
        }

        if (isset($privInfo['description'])) {
            $xsp->appendChild($doc->createElementNS('DAV:','d:description',$privInfo['description']));
        }

        if (isset($privInfo['privileges'])) { 
            foreach($privInfo['privileges'] as $subPrivName=>$subPrivInfo) {
                $this->serializePriv($doc,$xsp,$subPrivName,$subPrivInfo);
            }
        }

    }

}
