<?php

namespace Sabre\CalDAV\Xml\Property;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\DAV\Exception\CannotDeserialize,
    Sabre\CalDAV\Plugin;

/**
 * AllowedSharingModes
 *
 * This property encodes the 'allowed-sharing-modes' property, as defined by
 * the 'caldav-sharing-02' spec, in the http://calendarserver.org/ns/
 * namespace.
 *
 * This property is a representation of the supported-calendar_component-set
 * property in the CalDAV namespace. It simply requires an array of components,
 * such as VEVENT, VTODO
 *
 * @see https://trac.calendarserver.org/browser/CalendarServer/trunk/doc/Extensions/caldav-sharing-02.txt
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class AllowedSharingModes implements Element {

    /**
     * Whether or not a calendar can be shared with another user
     *
     * @var bool
     */
    protected $canBeShared;

    /**
     * Whether or not the calendar can be placed on a public url.
     *
     * @var bool
     */
    protected $canBePublished;

    /**
     * Constructor
     *
     * @param bool $canBeShared
     * @param bool $canBePublished
     * @return void
     */
    public function __construct($canBeShared, $canBePublished) {

        $this->canBeShared = $canBeShared;
        $this->canBePublished = $canBePublished;

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
    public function serializeXml(Writer $writer) {

        if ($this->canBeShared) {
            $writer->writeElement('{' . Plugin::NS_CALENDARSERVER . '}can-be-shared');
        }
        if ($this->canBePublished) {
            $writer->writeElement('{' . Plugin::NS_CALENDARSERVER . '}can-be-published');
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
    static public function deserializeXml(Reader $reader) {

        throw new CannotDeserialize('This element does not have a deserializer');

    }
}
