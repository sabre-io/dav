<?php

/**
 * Href property
 *
 * The href property represpents a url within a {DAV:}href element.
 * This is used by many WebDAV extensions, but not really within the WebDAV core spec
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Property_Href extends Sabre_DAV_Property  {

    /**
     * href 
     * 
     * @var string 
     */
    private $href;

    /**
     * __construct 
     * 
     * @param string $href 
     * @return void
     */
    public function __construct($href) {

        $this->href = $href;

    }

    /**
     * Returns the uri 
     * 
     * @return string 
     */
    public function getHref() {

        return $this->href;

    }

    /**
     * Serializes this property.
     *
     * It will additionally prepend the href property with the server's base uri.
     * 
     * @param Sabre_DAV_Server $server 
     * @param DOMElement $dom 
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $dom) {

        $elem = $dom->ownerDocument->createElementNS('DAV:','d:href');
        $elem->nodeValue = $server->getBaseUri() . $this->href;
        $dom->appendChild($elem);

    }

}
