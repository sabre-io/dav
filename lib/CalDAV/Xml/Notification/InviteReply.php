<?php

namespace Sabre\CalDAV\Xml\Notification;

use
    Sabre\Xml\Element,
    Sabre\Xml\Reader,
    Sabre\Xml\Writer,
    Sabre\DAV,
    Sabre\CalDAV,
    Sabre\CalDAV\SharingPlugin;

/**
 * This class represents the cs:invite-reply notification element.
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class InviteReply implements NotificationInterface {

    /**
     * A unique id for the message
     *
     * @var string
     */
    protected $id;

    /**
     * Timestamp of the notification
     *
     * @var DateTime
     */
    protected $dtStamp;

    /**
     * The unique id of the notification this was a reply to.
     *
     * @var string
     */
    protected $inReplyTo;

    /**
     * A url to the recipient of the original (!) notification.
     *
     * @var string
     */
    protected $href;

    /**
     * The type of message, see the SharingPlugin::STATUS_ constants.
     *
     * @var int
     */
    protected $type;

    /**
     * A url to the shared calendar.
     *
     * @var string
     */
    protected $hostUrl;

    /**
     * A description of the share request
     *
     * @var string
     */
    protected $summary;

    /**
     * Notification Etag
     *
     * @var string
     */
    protected $etag;

    /**
     * Creates the Invite Reply Notification.
     *
     * This constructor receives an array with the following elements:
     *
     *   * id           - A unique id
     *   * etag         - The etag
     *   * dtStamp      - A DateTime object with a timestamp for the notification.
     *   * inReplyTo    - This should refer to the 'id' of the notification
     *                    this is a reply to.
     *   * type         - The type of notification, see SharingPlugin::STATUS_*
     *                    constants for details.
     *   * hostUrl      - A url to the shared calendar.
     *   * summary      - Description of the share, can be the same as the
     *                    calendar, but may also be modified (optional).
     */
    public function __construct(array $values) {

        $required = array(
            'id',
            'etag',
            'href',
            'dtStamp',
            'inReplyTo',
            'type',
            'hostUrl',
        );
        foreach($required as $item) {
            if (!isset($values[$item])) {
                throw new \InvalidArgumentException($item . ' is a required constructor option');
            }
        }

        foreach($values as $key=>$value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException('Unknown option: ' . $key);
            }
            $this->$key = $value;
        }

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

        $writer->writeElement('{' . CalDAV\Plugin::NS_CALENDARSERVER .'}invite-reply');

    }

    /**
     * This method serializes the entire notification, as it is used in the
     * response body.
     *
     * @param Writer $writer
     * @return void
     */
    public function serializeFullXml(Writer $writer) {

        $cs = '{' . CalDAV\Plugin::NS_CALENDARSERVER . '}';

        $this->dtStamp->setTimezone(new \DateTimezone('GMT'));
        $writer->writeElement($cs . 'dtstamp', $this->dtStamp->format('Ymd\\THis\\Z'));

        $writer->startElement($cs . 'invite-reply');

        $writer->writeElement($cs . 'uid', $this->id);
        $writer->writeElement($cs . 'in-reply-to', $this->inReplyTo);
        $writer->writeElement('{DAV:}href', $this->href);

        switch($this->type) {

            case SharingPlugin::STATUS_ACCEPTED :
                $writer->writeElement($cs . 'invite-accepted');
                break;
            case SharingPlugin::STATUS_DECLINED :
                $writer->writeElement($cs . 'invite-declined');
                break;

        }

        $writer->writeElement($cs . 'hosturl', [
            '{DAV:}href' => $writer->baseUri . $this->hostUrl
            ]);

        if ($this->summary) {
            $writer->writeElement($cs . 'summary', $this->summary);
        }
        $writer->endElement(); // invite-reply

    }

    /**
     * Returns a unique id for this notification
     *
     * This is just the base url. This should generally be some kind of unique
     * id.
     *
     * @return string
     */
    public function getId() {

        return $this->id;

    }

    /**
     * Returns the ETag for this notification.
     *
     * The ETag must be surrounded by literal double-quotes.
     *
     * @return string
     */
    public function getETag() {

        return $this->etag;

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
