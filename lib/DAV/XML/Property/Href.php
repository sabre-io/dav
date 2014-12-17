<?php

namespace Sabre\DAV\XML\Property;

use
    Sabre\XML\Element,
    Sabre\XML\Reader,
    Sabre\XML\Writer;

/**
 * Href property
 *
 * This class represents any WebDAV property that contains a {DAV:}href
 * element, and there are many.
 *
 * It can support either 1 or more hrefs. If while unserializing no valid
 * {DAV:}href elements were found, this property will unserialize itself as
 * null.
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Href implements Element {

    /**
     * List of uris
     *
     * @var array
     */
    protected $hrefs;

    /**
     * Automatically prefix the url with the server base directory
     *
     * @var bool
     */
    protected $autoPrefix = true;

    /**
     * Constructor
     *
     * You must either pass a string for a single href, or an array of hrefs.
     *
     * If auto-prefix is set to false, the hrefs will be treated as absolute
     * and not relative to the servers base uri.
     *
     * @param string|string[] $href
     * @param bool $autoPrefix
     */
    public function __construct($hrefs, $autoPrefix = true) {

        if (is_string($hrefs)) {
            $hrefs = [$hrefs];
        }
        $this->hrefs = $hrefs;
        $this->autoPrefix = $autoPrefix;


    }

    /**
     * Returns the first Href.
     *
     * @return string
     */
    public function getHref() {

        return $this->hrefs[0];

    }

    /**
     * Returns the hrefs as an array
     *
     * @return array
     */
    public function getHrefs() {

        return $this->hrefs;

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

        foreach($this->getHrefs() as $href) {
            if ($this->autoPrefix) {
                $href = $writer->baseUri . $href;
            }
            $writer->writeElement('{DAV:}href', $href);
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
    static public function xmlDeserialize(Reader $reader) {

        $hrefs = [];
        foreach($reader->parseInnerTree() as $elem) {
            if ($elem['name'] !== '{DAV:}href')
                continue;

            $hrefs[] = $elem['value'];

        }
        if ($hrefs) {
            return new self($hrefs);
        }

    }

}
