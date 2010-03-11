<?php

class Sabre_DAV_Property_Principal extends Sabre_DAV_Property {

    const UNAUTHENTICATED = 1;
    const AUTHENTICATED = 2;
    const HREF = 3;

    private $type;
    private $href;

    function __construct($type, $href = null) {

        $this->type = $type;
        $this->href = $href;

    }

    function getType() {

        return $this->type;

    }

    function getHref() {

        return $this->href;

    }

    function serialize(Sabre_DAV_Server $server, DOMElement $node) {

        switch($this->type) {

            case self::UNAUTHENTICATED :
                $node->appendChild(
                    $node->ownerDocument->createElementNS('DAV:','d:unauthenticated')
                );
                break;
            case self::AUTHENTICATED :
                $node->appendChild(
                    $node->ownerDocument->createElementNS('DAV:','d:authenticated')
                );
                break;
            case self::HREF :
                $href = $node->ownerDocument->createElementNS('DAV:','d:href');
                $href->nodeValue = $server->getBaseUri() . $this->href;
                $node->appendChild($href);
                break;

        }

    }

}
