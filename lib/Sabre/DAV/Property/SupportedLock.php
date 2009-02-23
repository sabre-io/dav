<?php

class Sabre_DAV_Property_SupportedLock extends Sabre_DAV_Property {

    public $supportsLocks = false;

    public function __construct($supportsLocks) {

        $this->supportsLocks = $supportsLocks;

    }

    public function serialize(DOMElement $prop) {

        $doc = $prop->ownerDocument;

        if (!$this->supportsLocks) return null;

        $frag = $doc->createDocumentFragment();

        $frag->appendXML('<d:lockentry><d:lockscope><d:exclusive /></d:lockscope><d:locktype><d:write /></d:locktype></d:lockentry>');
        $frag->appendXML('<d:lockentry><d:lockscope><d:shared /></d:lockscope><d:locktype><d:write /></d:locktype></d:lockentry>');

        $prop->appendChild($frag);
 
    }

}

?>
