<?php

use Sabre_CalDAV_SharingPlugin as SharingPlugin;

/**
 * This class represents the cs:invite-notification notification element.
 *
 * @package Sabre
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Notifications_Notification_Invite extends Sabre_DAV_Property implements Sabre_CalDAV_Notifications_INotificationType {

    /**
     * A unique id for the message
     *
     * @var string
     */
    protected $id;

    /**
     * A url to the recipient of the notification
     *
     * @var string
     */
    protected $href;

    /**
     * The type of message, see the SharingPlugin::STATUS_* constants.
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
     * Url to the sharer of the calendar
     *
     * @var string
     */
    protected $organizer;

    /**
     * The name of the sharer.
     *
     * @var string
     */
    protected $commonName;

    /**
     * A description of the share request
     *
     * @var string
     */
    protected $summary;

    /**
     * The Etag for the notification
     *
     * @var string
     */
    protected $etag;

    /**
     * Creares the Invite notification
     *
     * @param string $id A unique id
     * @param string $etag The etag
     * @param string $href A url to the recipient of the notification
     * @param int $type The type of message, see the SharingPlugin::STATUS_* constants.
     * @param bool $readOnly True if access to a calendar is read-only.
     * @param string $hostUrl A url to the shared calendar
     * @param string $organizer Url to the sharer of the calendar
     * @param string $commonName The name of the sharer (optional)
     * @param string $summary A description of the share request
     */
    public function __construct($id, $etag, $href, $type, $readOnly, $hostUrl, $organizer, $commonName = null, $summary = null) {

        $this->id = $id;
        $this->etag = $etag;
        $this->href = $href;
        $this->type = $type;
        $this->readOnly = $readOnly;
        $this->hostUrl = $hostUrl;
        $this->organizer = $organizer;
        $this->commonName = $commonName;
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

        $prop = $node->ownerDocument->createElement('cs:invite-notification');
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
        $prop = $doc->createElement('cs:invite-notification');
        $node->appendChild($prop);

        $uid = $doc->createElement('cs:uid');
        $uid->appendChild( $doc->createTextNode($this->id) );
        $prop->appendChild($uid);

        $href = $doc->createElement('d:href');
        $href->appendChild( $doc->createTextNode( $this->href ) );
        $prop->appendChild($href);

        $nodeName = null;
        switch($this->type) {

            case SharingPlugin::STATUS_ACCEPTED :
                $nodeName = 'cs:invite-accepted';
                break;
            case SharingPlugin::STATUS_DECLINED :
                $nodeName = 'cs:invite-declined';
                break;
            case SharingPlugin::STATUS_DELETED :
                $nodeName = 'cs:invite-deleted';
                break;
            case SharingPlugin::STATUS_NORESPONSE :
                $nodeName = 'cs:invite-noresponse';
                break;

        }
        $prop->appendChild(
            $doc->createElement($nodeName)
        );
        $hostHref = $doc->createElement('d:href', $server->getBaseUri() . $this->hostUrl);
        $hostUrl  = $doc->createElement('cs:hosturl');
        $hostUrl->appendChild($hostHref);
        $prop->appendChild($hostUrl);

        $organizerHref = $doc->createElement('d:href', $server->getBaseUri() . $this->organizer);
        $organizerUrl  = $doc->createElement('cs:organizer');
        if ($this->commonName) {
            $commonName = $doc->createElement('cs:common-name');
            $commonName->appendChild($doc->createTextNode($this->commonName));
            $organizerUrl->appendChild($commonName);
        }

        $organizerUrl->appendChild($organizerHref);
        $prop->appendChild($organizerUrl);

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

}
