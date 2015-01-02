<?php

namespace Sabre\CalDAV\Xml\Request;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\DAV\Exception\CannotSerialize,
    Sabre\CalDAV\Plugin;

/**
 * Share POST request parser
 *
 * This class parses the share POST request, as defined in:
 *
 * http://svn.calendarserver.org/repository/calendarserver/CalendarServer/trunk/doc/Extensions/caldav-sharing.txt
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Share implements Element {

    /**
     * The list of new people added or updated.
     *
     * Every element has the following keys:
     * 1. href - An email address
     * 2. commonName - Some name
     * 3. summary - An optional description of the share
     * 4. readOnly - true or false
     *
     * @var array
     */
    public $set = [];

    /**
     * List of people removed from the share list.
     *
     * The list is a flat list of email addresses (including mailto:).
     *
     * @var array
     */
    public $remove = [];

    /**
     * Constructor
     *
     * @param array $set
     * @param array $remove
     */
    public function __construct(array $set, array $remove) {

        $this->set = $set;
        $this->remove = $remove;

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

        throw new CannotSerialize('This element cannot be serialized.');

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

        $elems = $reader->parseInnerTree();

        $set = [];

        foreach($elems as $elem) {
            switch($elem['name']) {

                case '{'.Plugin::NS_CALENDARSERVER.'}set' :
                    $sharee = $elem['value'];

                    $sumElem = '{'.Plugin::NS_CALENDARSERVER.'}summary';

                    $set[] = [
                        'href'       => $sharee['{DAV:}href'],
                        'commonName' => $sharee['{'.Plugin::NS_CALENDARSERVER.'}common-name'],
                        'summary'    => isset($sharee[$sumElem])?$sharee[$sumElem]:null,
                        'readOnly'   => isset($sharee['{' . Plugin::NS_CALENDARSERVER . '}readOnly']),
                    ];
                    break;

                case '{'.Plugin::NS_CALENDARSERVER.'}remove' :
                    $remove[] = $elem['value']['{DAV:}href'];
                    break;

            }
        }

        return new self($set, $remove);

    }

}
