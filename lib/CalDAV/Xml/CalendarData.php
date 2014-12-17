<?php

namespace Sabre\CalDAV\XML;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\DAV\Exception\CannotSerialize,
    Sabre\DAV\Exception\BadRequest,
    Sabre\CalDAV\Plugin,
    Sabre\VObject\DateTimeParser;


/**
 * CalendarData parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:caldav}calendar-data XML
 * element, as defined in:
 *
 * https://tools.ietf.org/html/rfc4791#section-9.6
 *
 * This element is used for three distinct purposes:
 *
 * 1. To return calendar-data in a response, in which case this deserializer
 *    will just spit out a string.
 * 2. Information on how calendar-data should be returned, from a
 *    calendar-multiget or calendar-query REPORT, in which case this
 *    deserializer will spit out and array with the relevant information.
 * 3. A list of supported media-types, nested in the supported-calendar-data
 *    property. This case is currently not handled.
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class CalendarData implements Element {

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

        $value = $reader->parseInnerTree();

        // If we are parsing this as a string, it must have been an iCalendar
        // blob, and we can just return it as-is.
        if (is_string($value) || is_null($value)) {
            return $value;
        }

        $result = [
        ];
        foreach($value as $elem) {

            if ($elem['name'] === '{' . Plugin::NS_CALDAV . '}expand') {

                $result['expand'] = [
                    'start' => isset($elem['attributes']['start'])?DateTimeParser::parseDateTime($elem['attributes']['start']):null,
                    'end' => isset($elem['attributes']['end'])?DateTimeParser::parseDateTime($elem['attributes']['end']):null,
                ];

                if (!$result['expand']['start'] || !$result['expand']['end']) {
                    throw new BadRequest('The "start" and "end" attributes are required when expanding calendar-data');
                }
                if ($result['expand']['end'] <= $result['expand']['start']) {
                    throw new BadRequest('The end-date must be larger than the start-date when expanding calendar-data');
                }

            }

        }

        return $result;

    }

}
