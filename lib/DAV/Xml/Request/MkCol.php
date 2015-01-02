<?php

namespace Sabre\DAV\Xml\Request;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\DAV\Exception\CannotSerialize;

/**
 * WebDAV Extended MKCOL request parser.
 *
 * This class parses the {DAV:}mkol request, as defined in:
 *
 * https://tools.ietf.org/html/rfc5689#section-5.1
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class MkCol implements Element {

    /**
     * The list of properties that will be set.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Returns a key=>value array with properties that are supposed to get set
     * during creation of the new collection.
     *
     * @return array
     */
    function getProperties() {

        return $this->properties;

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

        throw new CannotSerialize('This element cannot be serialized.');

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

        $self = new self();

        $elems = $reader->parseInnerTree();

        foreach($elems as $elem) {
            if ($elem['name'] === '{DAV:}set') {
                $self->properties = array_merge($self->properties, $elem['value']['{DAV:}prop']);
            }
        }

        return $self;

    }

}
