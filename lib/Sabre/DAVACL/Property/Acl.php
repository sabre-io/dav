<?php

/**
 * This class represents the {DAV:}acl property 
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Property_Acl extends Sabre_DAV_Property {

    /**
     * List of privileges 
     * 
     * @var array 
     */
    private $privileges;

    /**
     * Constructor
     *
     * This object requires a structure similar to the return value from 
     * Sabre_DAVACL_Plugin::getACL() 
     * 
     * @param array $privileges 
     */
    public function __construct(array $privileges) {

        $this->privileges = $privileges;

    }

    /**
     * Serializes the property into a DOMElement 
     * 
     * @param Sabre_DAV_Server $server 
     * @param DOMElement $node 
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $node) {

        $doc = $node->ownerDocument;
        foreach($this->privileges as $ace) {

            $this->serializeAce($doc, $node, $ace, $server);

        }

    }

    /**
     * Serializes a single access control entry. 
     * 
     * @param DOMDocument $doc 
     * @param DOMElement $node 
     * @param array $ace
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    private function serializeAce($doc,$node,$ace, $server) {

        $xace  = $doc->createElementNS('DAV:','d:ace');
        $node->appendChild($xace);

        $principal = $doc->createElementNS('DAV:','d:principal');
        $xace->appendChild($principal);
        $principal->appendChild($doc->createElementNS('DAV:','d:href',$server->getBaseUri() . $ace['principal'] . '/'));

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
