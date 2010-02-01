<?php

class Sabre_CalDAV_Property_SupportedCalendarComponentSet extends Sabre_DAV_Property {

    private $components;

    function __construct(array $components) {

       $this->components = $components; 

    }
    
    function serialize(Sabre_DAV_Server $server,DOMElement $node) {

       $doc = $node->ownerDocument;
       foreach($this->components as $component) {

            $xcomp = $doc->createElement('cal:comp');
            $xcomp->setAttribute('name',$component);
            $node->appendChild($xcomp); 

       }

    }

}
