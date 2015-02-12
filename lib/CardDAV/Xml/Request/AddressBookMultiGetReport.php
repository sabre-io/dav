<?php

namespace Sabre\CardDAV\Xml\Request;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * AddressBookMultiGetReport request parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:carddav}addressbook-multiget
 * REPORT, as defined in:
 *
 * http://tools.ietf.org/html/rfc6352#section-8.7
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class AddressBookMultiGetReport implements XmlDeserializable {

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
     * The deserialize method is called during xml parsing.
     *
     * This method is called statictly, this is because in theory this method
     * may be used as a type of constructor, or factory method.
     *
     * Often you want to return an instance of the current class, but you are
     * free to return other data as well.
     *
     * You are responsible for advancing the reader to the next element. Not
     * doing anything will result in a never-ending loop.
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
    static function deserializeXml(Reader $reader) {

        $elems = $reader->parseInnerTree();
        $hrefs = [];

        $properties = null;

        foreach($elems as $elem) {

            switch($elem['name']) {

                case '{DAV:}prop' :
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

        return $obj;

    }

}
