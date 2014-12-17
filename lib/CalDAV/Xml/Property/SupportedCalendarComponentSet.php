<?php

namespace Sabre\CalDAV\Xml\Property;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\CalDAV\Plugin;

/**
 * SupportedCalendarComponentSet property.
 *
 * This class represents the
 * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set property, as
 * defined in:
 *
 * https://tools.ietf.org/html/rfc4791#section-5.2.3
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SupportedCalendarComponentSet implements Element {

    /**
     * List of supported components.
     *
     * This array will contain values such as VEVENT, VTODO and VJOURNAL.
     *
     * @var array
     */
    protected $components = [];

    /**
     * Creates the property.
     *
     * @param array $components
     */
    function __construct(array $components) {

        $this->components = $components;

    }

    /**
     * Returns the list of supported components
     *
     * @return array
     */
    function getValue() {

        return $this->components;

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

       foreach($this->components as $component) {

            $writer->startElement('{' . Plugin::NS_CALDAV . '}comp');
            $writer->writeAttributes(['name' => $component]);
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

        $elems = $reader->parseInnerTree();

        $components = [];

        foreach($elems as $elem) {
            if ($elem['name'] === '{'.Plugin::NS_CALDAV . '}comp') {
                $components[] = $elem['attributes']['name'];
            }
        }

        return new self($components);

    }

}
