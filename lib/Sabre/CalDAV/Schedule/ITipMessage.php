<?php

namespace Sabre\CalDAV\Schedule;

/**
 * This class represents an ITipMessage.
 *
 * This object contains all the information about a scheduling operation. Who
 * sent it, where it should go to, etc...
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class ITipMessage {

    /**
     * The senders' email address.
     *
     * Note that this does not imply that this has to be used in a From: field
     * if the message is sent by email. It may also be populated in Reply-To:
     * or not at all.
     *
     * @var string
     */
    public $sender;

    /**
     * The name of the sender. This is often populated from a CN parameter from
     * either the ORGANIZER or ATTENDEE, depending on the message.
     *
     * @var string|null
     */
    public $senderName;

    /**
     * The recipient's email address.
     *
     * @var string
     */
    public $recipient;

    /**
     * The name of the recipient. This is usually populated with the CN
     * parameter from the ATTENDEE or ORGANIZER property, if it's available.
     *
     * @var string|null
     */
    public $recipientName;

    /**
     * The contents of the METHOD property on the iCalendar body.
     *
     * @var string
     */
    public $method;

    /**
     * After the message has been delivered, this should contain a string such
     * as : 1.1;Sent or 1.2;Delivered.
     *
     * In case of a failure, this will hold the error status code.
     *
     * See:
     * http://tools.ietf.org/html/rfc6638#section-7.3
     *
     * @var string
     */
    public $scheduleStatus;

    /**
     * The iCalendar / iTip body.
     *
     * @var \Sabre\VObject\Component\VCalendar
     */
    public $message;

}
