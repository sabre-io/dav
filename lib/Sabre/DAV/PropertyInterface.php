<?php

namespace Sabre\DAV;

/**
 * PropertyInterface
 *
 * Implement this interface to create new complex properties
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface PropertyInterface {

    /**
     * Serializes this property into an XML document.
     *
     * @param Server $server
     * @param \DOMElement $prop
     * @return void
     */
    public function serialize(Server $server, \DOMElement $prop);

    /**
     * This method unserializes the property FROM an xml document.
     *
     * This method (often) must return an instance of itself. It acts therefore
     * a bit like a constructor. It is also valid to return a different object
     * or type.
     *
     * @param \DOMElement $prop
     * @param array $propertyMap
     * @return mixed
     */
    static public function unserialize(\DOMElement $prop, array $propertyMap);

}

