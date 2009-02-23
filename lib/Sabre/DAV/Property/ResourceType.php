<?php

class Sabre_DAV_Property_ResourceType extends Sabre_DAV_Property {

    public $resourceType;

    function __construct($resourceType) {

        $this->resourceType = $resourceType;

    }

    function serialize(DOMElement $prop) {

        if ($this->resourceType == Sabre_DAV_Server::NODE_FILE)
            return null;

        if ($this->resourceType == Sabre_DAV_Server::NODE_DIRECTORY) {

            $prop->appendChild($prop->ownerDocument->createElementNS('DAV:','d:collection'));

        }

    }

}
