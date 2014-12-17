<?php

namespace Sabre\DAV\Xml\Response;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\DAV\Exception\CannotSerialize;

/**
 * WebDAV MultiStatus parser
 *
 * This class parses the {DAV:}multistatus response, as defined in:
 * https://tools.ietf.org/html/rfc4918#section-14.16
 *
 * And it also adds the {DAV:}synctoken change from:
 * http://tools.ietf.org/html/rfc6578#section-6.4
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class MultiStatus implements Element {

    /**
     * The responses
     *
     * @var \Sabre\DAV\Xml\Element\Response[]
     */
    protected $responses;

    /**
     * A sync token (from RFC6578).
     *
     * @var string
     */
    protected $syncToken;

    /**
     * Constructor
     *
     * @param \Sabre\DAV\Xml\Element\Response[] $responses
     */
    function __construct(array $responses, $syncToken = null) {

        $this->responses = $responses;
        $this->syncToken = $syncToken;

    }

    /**
     * Returns the response list.
     *
     * @return \Sabre\DAV\Xml\Element\Response[]
     */
    function getResponses() {

        return $this->responses;

    }

    /**
     * Returns the sync-token, if available.
     *
     * @return string|null
     */
    function getSyncToken() {

        return $this->syncToken;

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
    function xmlSerialize(Writer $writer) {

        foreach($this->getResponses() as $response) {
            $writer->writeElement('{DAV:}response', $response);
        }
        if ($syncToken = $this->getSyncToken()) {
            $writer->writeElement('{DAV:}sync-token', $syncToken);
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
    static function xmlDeserialize(Reader $reader) {

        $elements = $reader->parseInnerTree();

        $responses = [];
        $syncToken = null;

        if ($elements) foreach($elements as $elem) {
            if ($elem['name'] === '{DAV:}response') {
                $responses[] = $elem['value'];
            }
            if ($elem['name'] === '{DAV:}sync-token') {
                $syncToken = $elem['value'];
            }
        }

        return new self($responses, $syncToken);

    }

}
