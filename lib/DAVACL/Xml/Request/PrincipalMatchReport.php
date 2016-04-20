<?php

namespace Sabre\DAVACL\Xml\Request;

use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\Reader;
use Sabre\Xml\Deserializer;

/**
 * PrincipalMatchReport request parser.
 *
 * This class parses the {DAV:}principal-match REPORT, as defined
 * in:
 *
 * https://tools.ietf.org/html/rfc3744#section-9.3
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PrincipalMatchReport implements XmlDeserializable {

    const SELF = 1;
    const PRINCIPAL_PROPERTY = 2;

    public $type;

    public $properties = [];

    public $principalProperty;

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
        
        $reader->pushContext();
        $reader->elementMap['{DAV:}prop'] = 'Sabre\Xml\Deserializer\enum';

        $elems = Deserializer\keyValue(
            $reader,
            'DAV:'
        );

        $reader->popContext();

        $principalMatch = new self();

        $principalMatch->type = 'carrot';

        if (array_key_exists('self', $elems)) {
            $principalMatch->type = self::SELF;
        }

        if (array_key_exists('principal-property', $elems)) {
            $principalMatch->type = self::PRINCIPAL_PROPERTY;
            $principalMatch->principalProperty = $elems['principal-property'][0]['name'];
        }

        if (!empty($elems['prop'])) {
            $principalMatch->properties = $elems['prop'];
        }

        return $principalMatch;

    }

}
