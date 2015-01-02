<?php

namespace Sabre\CalDAV\Xml\Property;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\Xml\Element\Elements,
    Sabre\CalDAV\Plugin;

/**
 * schedule-calendar-transp property.
 *
 * This property is a representation of the schedule-calendar-transp property.
 * This property is defined in:
 *
 * http://tools.ietf.org/html/rfc6638#section-9.1
 *
 * Its values are either 'transparent' or 'opaque'. If it's transparent, it
 * means that this calendar will not be taken into consideration when a
 * different user queries for free-busy information. If it's 'opaque', it will.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class ScheduleCalendarTransp implements Element {

    const TRANSPARENT = 'transparent';
    const OPAQUE = 'opaque';

    /**
     * value
     *
     * @var string
     */
    protected $value;

    /**
     * Creates the property
     *
     * @param string $value
     */
    public function __construct($value) {

        if ($value !== self::TRANSPARENT && $value !== self::OPAQUE) {
            throw new \InvalidArgumentException('The value must either be specified as "transparent" or "opaque"');
        }
        $this->value = $value;

    }

    /**
     * Returns the current value
     *
     * @return string
     */
    public function getValue() {

        return $this->value;

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

        switch($this->value) {
            case self::TRANSPARENT :
                $writer->writeElement('{'.Plugin::NS_CALDAV.'}transparent');
                break;
            case self::OPAQUE :
                $writer->writeElement('{'.Plugin::NS_CALDAV.'}opaque');
                break;
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

        $elems = Elements::deserializeXml($reader);

        $value = null;

        foreach($elems as $elem) {
            switch($elem) {
                case '{' . Plugin::NS_CALDAV . '}opaque' :
                    $value = self::OPAQUE;
                    break;
                case '{' . Plugin::NS_CALDAV . '}transparent' :
                    $value = self::TRANSPARENT;
                    break;
            }
        }
        if (is_null($value))
           return null;

        return new self($value);

    }

}
