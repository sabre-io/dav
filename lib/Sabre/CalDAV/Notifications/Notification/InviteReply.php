<?php

use Sabre_CalDAV_SharingPlugin as SharingPlugin;

/**
 * This class represents the cs:invite-reply notification element.
 *
 * @package Sabre
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Notifications_Notification_InviteReply extends Sabre_DAV_Property implements Sabre_CalDAV_Notifications_INotificationType {

    /**
     * A unique id for the message
     *
     * @var string
     */
    protected $id;

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
     * True if access to a calendar is read-only.
     *
     * @var bool
     */
    protected $readOnly;

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
     * Creares the Invite notification
     *
     * @param string $id A unique id
     * @param string $inReplyTo Which notification id this is a reply to
     * @param string $href A url to the recipient of the original(!) recipient
     * @param int $type The type of message, see the SharingPlugin::STATUS_* constants.
     * @param bool $readOnly True if access to a calendar is read-only.
     * @param string $hostUrl A url to the shared calendar
     * @param string $summary A description of the share request
     */
    public function __construct($id, $inReplyTo, $href, $type, $readOnly, $hostUrl, $summary = null) {

        $this->id = $id;
        $this->inReplyTo = $inReplyTo;
        $this->href = $href;
        $this->type = $type;
        $this->readOnly = $readOnly;
        $this->hostUrl = $hostUrl;
        $this->summary = $summary;

    }

    /**
     * Serializes the notification as a single property.
     *
     * You should usually just encode the single top-level element of the
     * notification.
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server, \DOMElement $node) {

        $prop = $node->ownerDocument->createElement('cs:invite-reply');
        $node->appendChild($prop);

    }

    /**
     * This method serializes the entire notification, as it is used in the
     * response body.
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serializeBody(Sabre_DAV_Server $server, \DOMElement $node) {

        $doc = $node->ownerDocument;
        $prop = $doc->createElement('cs:invite-reply');
        $node->appendChild($prop);

        $uid = $doc->createElement('cs:uid');
        $uid->appendChild($doc->createTextNode($this->id));
        $prop->appendChild($uid);

        $inReplyTo = $doc->createElement('cs:in-reply-to');
        $inReplyTo->appendChild( $doc->createTextNode($this->inReplyTo) );
        $prop->appendChild($inReplyTo);

        $href = $doc->createElement('d:href');
        $href->appendChild( $doc->createTextNode($this->href) );
        $prop->appendChild($href);

        $nodeName = null;
        switch($this->type) {

            case SharingPlugin::STATUS_ACCEPTED :
                $nodeName = 'cs:invite-accepted';
                break;
            case SharingPlugin::STATUS_DECLINED :
                $nodeName = 'cs:invite-declined';
                break;

        }
        $prop->appendChild(
            $doc->createElement($nodeName)
        );
        $hostHref = $doc->createElement('d:href', $server->getBaseUri() . $this->hostUrl);
        $hostUrl  = $doc->createElement('cs:hosturl');
        $hostUrl->appendChild($hostHref);
        $prop->appendChild($hostUrl);

        if ($this->summary) {
            $summary = $doc->createElement('cs:summary');
            $summary->appendChild($doc->createTextNode($this->summary));
            $prop->appendChild($summary);
        }

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

}
