<?php

namespace Sabre\CardDAV\Xml\Request;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;
use Sabre\DAV\Exception\BadRequest;
use Sabre\CardDAV\Plugin;

/**
 * AddressBookQueryReport request parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:carddav}addressbook-query
 * REPORT, as defined in:
 *
 * http://tools.ietf.org/html/rfc6352#section-8.6
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class AddressBookQueryReport implements XmlDeserializable {

    /**
     * An array with requested properties.
     *
     * @var array
     */
    public $properties;

    /**
     * List of property/component filters.
     *
     * @var array
     */
    public $filter;

    /**
     * The number of results the client wants
     *
     * null means it wasn't specified, which in most cases means 'all results'.
     *
     * @var int|null
     */
    public $limit;

    /**
     * Either 'anyof' or 'allof'
     *
     * @var string
     */
    public $test;

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
    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseInnerTree();

        $properties = null;
        $filter = null;
        $test = 'anyof';
        $limit = null;

        if (!is_array($elems)) $elems = [];

        foreach($elems as $elem) {

            switch($elem['name']) {

                case '{DAV:}prop' :
                    $properties = array_keys($elem['value']);
                    break;
                case '{'.Plugin::NS_CARDDAV.'}filter' :

                    if (!is_null($filter)) {
                        throw new BadRequest('You can only include 1 {' . Plugin::NS_CARDDAV . '}filter element');
                    }
                    if (isset($elem['attributes']['test'])) {
                        $test = $elem['attributes']['test'];
                        if ($test!=='allof' && $test!=='anyof') {
                            throw new BadRequest('The "test" attribute must be one of "allof" or "anyof"');
                        }
                    }

                    foreach($elem['value'] as $subElem) {
                        if ($subElem['name'] === '{' . Plugin::NS_CARDDAV . '}prop-filter') {
                            if (is_null($filter)) {
                                $filter = [];
                            }
                            $filter[] = $subElem['value'];
                        }
                    }
                    break;
                case '{'.Plugin::NS_CARDDAV.'}limit' :
                    foreach($elem['value'] as $child) {
                        if ($child['name'] === '{'. Plugin::NS_CARDDAV .'}nresults') {
                            $limit = (int)$child['value'];
                        }
                    }
                    break;

            }

        }

        if (is_null($filter)) {
            /**
             * We are supposed to throw this error, but KDE sometimes does not
             * include the filter element, and we need to treat it as if no
             * filters are supplied
             */
            //throw new BadRequest('The {' . Plugin::NS_CARDDAV . '}filter element is required for this request');
            $filter = [];

        }

        $obj = new self();
        $obj->properties = $properties;
        $obj->filter = $filter;
        $obj->test = $test;
        $obj->limit = $limit;

        return $obj;

    }

}
