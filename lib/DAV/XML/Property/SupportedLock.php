<?php

namespace Sabre\DAV\XML\Property;

use
    Sabre\DAV,
    Sabre\XML\Element,
    Sabre\XML\Reader,
    Sabre\XML\Writer;

/**
 * This class represents the {DAV:}supportedlock property.
 *
 * This property is defined here:
 * http://tools.ietf.org/html/rfc4918#section-15.10
 *
 * This property contains information about what kind of locks
 * this server supports.
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class SupportedLock implements Element {

    /**
     * supportsLocks
     *
     * @var bool
     */
    public $supportsLocks = false;

    /**
     * __construct
     *
     * @param bool $supportsLocks
     */
    public function __construct($supportsLocks) {

        $this->supportsLocks = $supportsLocks;

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
    public function xmlSerialize(Writer $writer) {

        if (!$this->supportsLocks) return null;

        $writer->writeElement('{DAV:}lockentry', [
            '{DAV:}lockscope' => ['{DAV:}exclusive' => null],        
            '{DAV:}locktype' =>  ['{DAV:}write' => null],        
        ]);
        $writer->writeElement('{DAV:}lockentry', [
            '{DAV:}lockscope' => ['{DAV:}shared' => null],        
            '{DAV:}locktype' =>  ['{DAV:}write' => null],        
        ]);

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
    static public function xmlDeserialize(Reader $reader) {

        throw new CannotDeserialize('This element does not have a deserializer');

    }
}

