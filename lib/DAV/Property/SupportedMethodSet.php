<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;

/**
 * supported-method-set property.
 *
 * This property is defined in RFC3253.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SupportedMethodSet extends DAV\Property {

    /**
     * List of methods
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * Creates the property
     *
     * Any reports passed in the constructor should be valid HTTP methods.
     *
     * @param string[] $methods
     */
    public function __construct(array $method) {

        $this->methods = $method;

    }

    /**
     * Returns the list of supported methods.
     *
     * @return string[]
     */
    public function getValue() {

        return $this->methods;

    }

    /**
     * Serializes the node
     *
     * @param DAV\Server $server
     * @param \DOMElement $prop
     * @return void
     */
    public function serialize(DAV\Server $server, \DOMElement $prop) {

        foreach($this->methods as $method) {

            $supportedMethod = $prop->ownerDocument->createElement('d:supported-method');
            $supportedMethod->setAttribute('name', $method);
            $prop->appendChild($supportedMethod);

        }

    }

}
