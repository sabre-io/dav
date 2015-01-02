<?php

namespace Sabre\CalDAV\Xml\Request;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\DAV\Exception\CannotSerialize,
    Sabre\CalDAV\Plugin;

/**
 * CalendarMultiGetReport request parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:caldav}calendar-multiget
 * REPORT, as defined in:
 *
 * https://tools.ietf.org/html/rfc4791#section-7.9
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class CalendarMultiGetReport implements Element {

    /**
     * An array with requested properties.
     *
     * @var array
     */
    public $properties;

    /**
     * This is an array with the urls that are being requested.
     *
     * @var array
     */
    public $hrefs;

    /**
     * If the calendar data must be expanded, this will contain an array with 2
     * elements: start and end.
     *
     * Each may be a DateTime or null.
     *
     * @var array|null
     */
    public $expand = null;

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
    public function serializeXml(Writer $writer) {

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
    static public function deserializeXml(Reader $reader) {

        $elems = $reader->parseInnerTree();
        $hrefs = [];

        $properties = null;

        $expand = false;

        foreach($elems as $elem) {

            switch($elem['name']) {

                case '{DAV:}prop' :
                    if (isset($elem['value']['{' . Plugin::NS_CALDAV . '}calendar-data']['expand'])) {
                        $expand = $elem['value']['{' . Plugin::NS_CALDAV . '}calendar-data']['expand'];
                    }
                    $properties = array_keys($elem['value']);
                    break;
                case '{DAV:}href' :
                    $hrefs[] = $elem['value'];
                    break;

            }

        }

        $obj = new self();
        $obj->properties = $properties;
        $obj->hrefs = $hrefs;
        $obj->expand = $expand;

        return $obj;

    }

}
