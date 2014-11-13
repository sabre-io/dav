<?php

namespace Sabre\DAV\XML\Property;

use
    Sabre\DAV,
    Sabre\XML\Element,
    Sabre\XML\Reader,
    Sabre\XML\Writer;

/**
 * Represents {DAV:}lockdiscovery property.
 *
 * This property is defined here:
 * http://tools.ietf.org/html/rfc4918#section-15.8
 *
 * This property contains all the open locks on a given resource
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class LockDiscovery implements Element {

    /**
     * locks
     *
     * @var \Sabre\DAV\Locks\LockInfo[]
     */
    public $locks;

    /**
     * Should we show the locktoken as well?
     *
     * @var bool
     */
    public $revealLockToken;

    /**
     * Hides the {DAV:}lockroot element from the response.
     *
     * It was reported that showing the lockroot in the response can break
     * Office 2000 compatibility.
     *
     * @var bool
     */
    static public $hideLockRoot = false;

    /**
     * __construct
     *
     * @param \Sabre\DAV\Locks\LockInfo[] $locks
     * @param bool $revealLockToken
     */
    public function __construct($locks, $revealLockToken = false) {

        $this->locks = $locks;
        $this->revealLockToken = $revealLockToken;

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

        foreach($this->locks as $lock) {

            $writer->startElement('{DAV:}activelock');

            $writer->startElement('{DAV:}lockscope');
            if ($lock->scope === 'shared') {
                $writer->writeElement('{DAV:}shared');
            } else {
                $writer->writeElement('{DAV:}exclusive');
            }

            $writer->endElement(); // {DAV:}lockscope

            $writer->startElement('{DAV:}locktype');
            $writer->writeElement('{DAV:}write');
            $writer->endElement(); // {DAV:}locktype

            if (!self::$hideLockRoot) {
                $writer->startElement('{DAV:}lockroot');
                $writer->writeElement('{DAV:}href', $writer->baseUri . $lock->uri);
                $writer->endElement(); // {DAV:}lockroot
            }
            $writer->writeElement('{DAV:}depth', ($lock->depth == DAV\Server::DEPTH_INFINITY?'infinity':$lock->depth));
            $writer->writeElement('{DAV:}timeout','Second-' . $lock->timeout);

            if ($this->revealLockToken) {
                $writer->startElement('{DAV:}locktoken');
                $writer->writeElement('{DAV:}href', 'opaquelocktoken:' . $lock->token);
                $writer->endElement(); // {DAV:}locktoken

            }

            $writer->writeElement('{DAV:}owner', $lock->owner);
            $writer->endElement(); // {DAV:}activelock

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

