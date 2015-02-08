<?php

namespace Sabre\CardDAV\XML\Property;

use
    Sabre\XML\Element,
    Sabre\XML\Reader,
    Sabre\XML\Writer,
    Sabre\DAV\Exception\CannotDeserialize,
    Sabre\CardDAV\Plugin;

/**
 * Supported-address-data property
 *
 * This property is a representation of the supported-address-data property
 * in the CardDAV namespace.
 *
 * This property is defined in:
 *
 * http://tools.ietf.org/html/rfc6352#section-6.2.2
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class SupportedAddressData implements Element {

    /**
     * supported versions
     *
     * @var array
     */
    protected $supportedData = array();

    /**
     * Creates the property
     *
     * @param array|null $supportedData
     */
    public function __construct(array $supportedData = null) {

        if (is_null($supportedData)) {
            $supportedData = array(
                array('contentType' => 'text/vcard', 'version' => '3.0'),
                // array('contentType' => 'text/vcard', 'version' => '4.0'),
            );
        }

       $this->supportedData = $supportedData;

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

        foreach($this->supportedData as $supported) {
            $writer->startElement('{' . Plugin::NS_CARDDAV . '}address-data-type');
            $writer->writeAttributes([
                'content-type' => $supported['contentType'],
                'version' => $supported['version']
                ]);
            $writer->endElement(); // address-data-type
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
