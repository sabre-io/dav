<?php

class Sabre_DAVACL_Property_Acl extends Sabre_DAV_Property {

    private $privileges;
    private $server;

    function __construct(array $privileges) {

        $this->privileges = $privileges;

    }

    function serialize(Sabre_DAV_Server $server,DOMElement $node) {

        $doc = $node->ownerDocument;
        $this->server = $server;
        foreach($this->privileges as $ace) {

            $this->serializeAce($doc,$node,$ace);

        }

    }

    function serializeAce($doc,$node,$ace) {

        $xace  = $doc->createElementNS('DAV:','d:ace');
        $node->appendChild($xace);

        $principal = $doc->createElementNS('DAV:','d:principal');
        $xace->appendChild($principal);
        $principal->appendChild($doc->createElementNS('DAV:','d:href',$this->server->getBaseUri() . $ace['principal'] . '/'));

        $grant = $doc->createElementNS('DAV:','d:grant');
        $xace->appendChild($grant);

        foreach($ace['grant'] as $privName) {

            $privParts = null;

            preg_match('/^{([^}]*)}(.*)$/',$privName,$privParts);

            $xprivilege = $doc->createElementNS('DAV:','d:privilege');
            $grant->appendChild($xprivilege);

            $xprivilege->appendChild($doc->createElementNS($privParts[1],'d:'.$privParts[2]));

        }

    }

}
