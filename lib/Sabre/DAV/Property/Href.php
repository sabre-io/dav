<?php

class Sabre_DAV_Property_Href extends Sabre_DAV_Property  {

    private $href;

    function __construct($href) {

        $this->href = $href;

    }

    public function getHref() {

        return $this->href;

    }

    function serialize(DOMElement $dom) {

        $elem = $dom->ownerDocument->createElementNS('DAV:','d:href');
        $elem->nodeValue = $this->href;
        $dom->appendChild($elem);

    }

}
