<?php

class Sabre_DAV_Property_ResourceType extends Sabre_DAV_Property {

    public $resourceType;

    function __construct($resourceType) {

        if ($resourceType == Sabre_DAV_Server::NODE_FILE)
            $this->resourceType = null;
        elseif ($resourceType == Sabre_DAV_Server::NODE_DIRECTORY)
            $this->resourceType = '{DAV:}collection';
        else 
            $this->resourceType = $resourceType;

    }

    function serialize(DOMElement $prop) {

        $propName = null;
        if (preg_match('/^{([^}]*)}(.*)$/',$this->resourceType,$propName)) { 
     
            $prop->appendChild($prop->ownerDocument->createElementNS($propName[1],'d:' . $propName[2]));
        
        }

    }

    function getValue() {

        return $this->resourceType;

    }

}
