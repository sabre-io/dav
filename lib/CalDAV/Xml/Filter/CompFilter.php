<?php

namespace Sabre\CalDAV\Xml\Filter;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\DAV\Exception\CannotSerialize,
    Sabre\DAV\Exception\BadRequest,
    Sabre\CalDAV\Plugin,
    Sabre\VObject\DateTimeParser;


/**
 * CompFilter parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:caldav}comp-filter XML
 * element, as defined in:
 *
 * https://tools.ietf.org/html/rfc4791#section-9.6
 *
 * The result will be spit out as an array.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class CompFilter implements Element {

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

        $result = [
            'name' => null,
            'is-not-defined' => false,
            'comp-filters' => [],
            'prop-filters' => [],
            'time-range' => false,
        ];

        $att = $reader->parseAttributes();
        $result['name'] = $att['name'];

        $elems = $reader->parseInnerTree();

        if (is_array($elems)) foreach($elems as $elem) {

            switch($elem['name']) {

                case '{' . Plugin::NS_CALDAV . '}comp-filter' :
                    $result['comp-filters'][] = $elem['value'];
                    break;
                case '{' . Plugin::NS_CALDAV . '}prop-filter' :
                    $result['prop-filters'][] = $elem['value'];
                    break;
                case '{' . Plugin::NS_CALDAV . '}is-not-defined' :
                    $result['is-not-defined'] = true;
                    break;
                case '{' . Plugin::NS_CALDAV . '}time-range' :
                    if ($result['name'] === 'VCALENDAR') {
                        throw new BadRequest('You cannot add time-range filters on the VCALENDAR component');
                    }
                    $result['time-range'] = [
                        'start' => isset($elem['attributes']['start'])?DateTimeParser::parseDateTime($elem['attributes']['start']):null,
                        'end' => isset($elem['attributes']['end'])?DateTimeParser::parseDateTime($elem['attributes']['end']):null,
                    ];
                    if($result['time-range']['start'] && $result['time-range']['end'] && $result['time-range']['end'] <= $result['time-range']['start']) {
                        throw new BadRequest('The end-date must be larger than the start-date');
                    }
                    break;
                default :
                    die('Unknown!' . $elem['name']);

            }

        }

        return $result;

    }

}
