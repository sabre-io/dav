<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\Reader;

/**
 * WebDAV PROPPATCH request parser.
 *
 * This class parses the {DAV:}propertyupdate request, as defined in:
 *
 * https://tools.ietf.org/html/rfc4918#section-14.20
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PropPatch implements XmlDeserializable {

    /**
     * The list of properties that will be updated and removed.
     *
     * If a property will be removed, it's value will be set to null.
     *
     * @var array
     */
    public $properties = [];

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

        $self = new self();

        $elementMap = $reader->elementMap;
        $elementMap['{DAV:}prop']   = 'Sabre\DAV\Xml\Element\Prop';
        $elementMap['{DAV:}set']    = 'Sabre\Xml\Element\KeyValue';
        $elementMap['{DAV:}remove'] = 'Sabre\Xml\Element\KeyValue';

        $elems = $reader->parseInnerTree($elementMap);

        foreach ($elems as $elem) {
            if ($elem['name'] === '{DAV:}set') {
                $self->properties = array_merge($self->properties, $elem['value']['{DAV:}prop']);
            }
            if ($elem['name'] === '{DAV:}remove') {

                // Ensuring there are no values.
                foreach ($elem['value']['{DAV:}prop'] as $remove => $value) {
                    $self->properties[$remove] = null;
                }

            }
        }

        return $self;

    }

}
