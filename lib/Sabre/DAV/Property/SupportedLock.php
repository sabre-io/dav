<?php

class Sabre_DAV_Property_SupportedLock extends Sabre_DAV_Property {

    public $supportsLocks = false;

    public function __construct($supportsLocks) {

        $this->supportsLocks = $supportsLocks;

    }

    public function serialize(DOMElement $prop) {

        $doc = $prop->ownerDocument;

        if (!$this->supportsLocks) return null;

        $lockEntry1 = $doc->createElementNS('DAV:','d:lockentry');
        $lockEntry2 = $doc->createElementNS('DAV:','d:lockentry');

        $prop->appendChild($lockEntry1);
        $prop->appendChild($lockEntry2);

        $lockScope1 = $doc->createElementNS('DAV:','d:lockscope');
        $lockScope2 = $doc->createElementNS('DAV:','d:lockscope');
        $lockType1 = $doc->createElementNS('DAV:','d:locktype');
        $lockType2 = $doc->createElementNS('DAV:','d:locktype');

        $lockEntry1->appendChild($lockScope1);
        $lockEntry1->appendChild($lockType1);
        $lockEntry2->appendChild($lockScope2);
        $lockEntry2->appendChild($lockType2);

        $lockScope1->appendChild($doc->createElementNS('DAV:','d:exclusive'));
        $lockScope2->appendChild($doc->createElementNS('DAV:','d:shared'));

        $lockType1->appendChild($doc->createElementNS('DAV:','d:write'));
        $lockType2->appendChild($doc->createElementNS('DAV:','d:write'));

        //$frag->appendXML('<d:lockentry><d:lockscope><d:exclusive /></d:lockscope><d:locktype><d:write /></d:locktype></d:lockentry>');
        //$frag->appendXML('<d:lockentry><d:lockscope><d:shared /></d:lockscope><d:locktype><d:write /></d:locktype></d:lockentry>');

    }

}

?>
