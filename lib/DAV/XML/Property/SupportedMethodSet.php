<?php

namespace Sabre\DAV\XML\Property;

use
    Sabre\DAV,
    Sabre\XML\Element,
    Sabre\XML\Reader,
    Sabre\XML\Writer,
    Sabre\XML\Element\Elements;

/**
 * supported-method-set property.
 *
 * This property is defined in RFC3253, but since it's
 * so common in other webdav-related specs, it is part of the core server.
 *
 * This property is defined here:
 * http://tools.ietf.org/html/rfc3253#section-3.1.3
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class SupportedMethodSet implements Element {

    /**
     * List of methods
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * Creates the property
     *
     * Any reports passed in the constructor
     * should be valid report-types in clark-notation.
     *
     * Either a string or an array of strings must be passed.
     *
     * @param string|string[] $methods
     */
    function __construct($methods = null) {

        $this->methods = (array)$methods;

    }

    /**
     * Returns the list of supported http methods.
     *
     * @return string[]
     */
    function getValue() {

        return $this->methods;

    }

    /**
     * Returns true or false if the property contains a specific method.
     *
     * @param string $methodName
     * @return bool
     */
    function has($methodName) {

        return in_array(
            $methodName,
            $this->methods
        );

    }

    /**
     * The serialize method is called during xml writing.
     *
     * It should use the $writer argument to encode this object into XML.
     *
     * Important note: it is not needed to create the parent element. The
     * parent element is already created, and we only have to worry about
     * attributes, child elements and text (if any).
     *
     * Important note 2: If you are writing any new elements, you are also
     * responsible for closing them.
     *
     * @param Writer $writer
     * @return void
     */
    function xmlSerialize(Writer $writer) {

        foreach($this->getValue() as $val) {
            $writer->startElement('{DAV:}supported-method');
            $writer->writeAttribute('name', $val);
            $writer->endElement();
        }

    }

    /**
     * The deserialize method is called during xml parsing.
     *
     * This method is called statictly, this is because in theory this method
     * may be used as a type of constructor, or factory method.
     *
     * Often you want to return an instance of the current class, but you are
     * free to return other data as well.
     *
     * Important note 2: You are responsible for advancing the reader to the
     * next element. Not doing anything will result in a never-ending loop.
     *
     * If you just want to skip parsing for this element altogether, you can
     * just call $reader->next();
     *
     * $reader->parseInnerTree() will parse the entire sub-tree, and advance to
     * the next element.
     *
     * @param Reader $reader
     * @return mixed
     */
    static function xmlDeserialize(Reader $reader) {

        throw new CannotDeserialize('This element does not have a deserializer');

    }

}
