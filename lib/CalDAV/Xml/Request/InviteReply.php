<?php

namespace Sabre\CalDAV\Xml\Request;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\Xml\Element\KeyValue,
    Sabre\DAV\Exception\CannotSerialize,
    Sabre\DAV\Exception\BadRequest,
    Sabre\CalDAV\Plugin,
    Sabre\CalDAV\SharingPlugin;

/**
 * Invite-reply POST request parser
 *
 * This class parses the invite-reply POST request, as defined in:
 *
 * http://svn.calendarserver.org/repository/calendarserver/CalendarServer/trunk/doc/Extensions/caldav-sharing.txt
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class InviteReply implements Element {

    /**
     * The sharee calendar user address.
     *
     * This is the address that the original invite was set to
     *
     * @var string
     */
    public $href;

    /**
     * The uri to the calendar that was being shared.
     *
     * @var string
     */
    public $calendarUri;

    /**
     * The id of the invite message that's being responded to
     *
     * @var string
     */
    public $inReplyTo;

    /**
     * An optional message
     *
     * @var string
     */
    public $summary;

    /**
     * Either SharingPlugin::STATUS_ACCEPTED or SharingPlugin::STATUS_DECLINED.
     *
     * @var int
     */
    public $status;

    /**
     * Constructor
     *
     * @param string $href
     * @param string $calendarUri
     * @param string $inReplyTo
     * @param string $summary
     * @param int $status
     */
    public function __construct($href, $calendarUri, $inReplyTo, $summary, $status) {

        $this->href = $href;
        $this->calendarUri = $calendarUri;
        $this->inReplyTo = $inReplyTo;
        $this->summary = $summary;
        $this->status = $status;

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

        $elems = KeyValue::deserializeXml($reader);

        $href = null;
        $calendarUri = null;
        $inReplyTo = null;
        $summary = null;
        $status = null;

        foreach($elems as $name=>$value) {

            switch($name) {

                case '{' . Plugin::NS_CALENDARSERVER . '}hosturl' :
                    foreach($value as $bla) {
                        if ($bla['name'] === '{DAV:}href') {
                            $calendarUri = $bla['value'];
                        }
                    }
                    break;
                case '{' . Plugin::NS_CALENDARSERVER . '}invite-accepted' :
                    $status = SharingPlugin::STATUS_ACCEPTED;
                    break;
                case '{' . Plugin::NS_CALENDARSERVER . '}invite-declined' :
                    $status = SharingPlugin::STATUS_DECLINED;
                    break;
                case '{' . Plugin::NS_CALENDARSERVER . '}in-reply-to' :
                    $inReplyTo = $value;
                    break;
                case '{' . Plugin::NS_CALENDARSERVER . '}summary' :
                    $summary = $value;
                    break;
                case '{DAV:}href' :
                    $href = $value;
                    break;
                default :
                    die('Death: ' . $name);
            }

        }
        if (is_null($calendarUri)) {
            throw new BadRequest('The {http://calendarserver.org/ns/}hosturl/{DAV:}href element must exist');
        }

        return new self($href, $calendarUri, $inReplyTo, $summary, $status);

    }

}
