<?php

namespace Sabre\DAV\Xml\Request;

use
    Sabre\Xml\Element,
    Sabre\Xml\Element\Elements,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\DAV\Exception\CannotSerialize;

/**
 * WebDAV PROPFIND request parser.
 *
 * This class parses the {DAV:}propfind request, as defined in:
 *
 * https://tools.ietf.org/html/rfc4918#section-14.20
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class PropFind implements Element {

    /**
     * If this is set to true, this was an 'allprop' request.
     *
     * @var bool
     */
    public $allProp = false;

    /**
     * The property list
     *
     * @var null|array
     */
    public $properties;

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
    public function xmlSerialize(Writer $writer) {

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
    static public function xmlDeserialize(Reader $reader) {

        $self = new self();

        $reader->read();

        do {

            if ($reader->nodeType === Reader::ELEMENT) {

                $clark = $reader->getClark();
                switch($reader->getClark()) {

                    case '{DAV:}allprop' :
                        $self->allProp = true;
                        break;
                    case '{DAV:}prop' :
                        $self->properties = Elements::xmlDeserialize($reader);
                        break;
                }
                $reader->next();

            } else {
                $reader->read();
            }

        } while ($reader->nodeType !== Reader::END_ELEMENT);

        $reader->read();
        return $self;

    }

}
