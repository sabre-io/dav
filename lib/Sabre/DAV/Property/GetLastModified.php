<?php

class Sabre_DAV_Property_GetLastModified extends Sabre_DAV_Property {

    public $time;

    function __construct($time) {

        if (!(int)$time) $time = strtotime($time);
        $this->time = $time;

    }

    public function serialize(DOMElement $prop) {

        $doc = $prop->ownerDocument;
        $prop->setAttribute('xmlns:b','urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882/');
        $prop->setAttribute('b:dt','dateTime.rfc1123');
        $prop->nodeValue = date(DATE_RFC1123,$this->time);

    }

    public function getTime() {

        return $this->time;

    }

}

?>
