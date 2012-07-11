<?php

class Sabre_CalDAV_Backend_SharingMock extends Sabre_CalDAV_Backend_Mock implements Sabre_CalDAV_Backend_SharingSupport {

    private $calendarData;
    private $calendars;
    private $notifications;

    function __construct(array $calendars, array $calendarData, array $notifications = array()) {

        parent::__construct($calendars, $calendarData, $notifications);

    }

    /**
     * Updates the list of shares.
     *
     * The first array is a list of people that are to be added to the
     * calendar.
     *
     * Every element in the add array has the following properties:
     *   * href - A url. Usually a mailto: address
     *   * commonName - Usually a first and last name, or false
     *   * summary - A description of the share, can also be false
     *   * readOnly - A boolean value
     *
     * Every element in the remove array is just the address string.
     *
     * Note that if the calendar is currently marked as 'not shared' by and
     * this method is called, the calendar should be 'upgraded' to a shared
     * calendar.
     *
     * @param mixed $calendarId
     * @param array $add
     * @param array $remove
     * @return void
     */
    function updateShares($calendarId, array $add, array $remove) {

    }

    /**
     * Returns the list of people whom this calendar is shared with.
     *
     * Every element in this array should have the following properties:
     *   * href - Often a mailto: address
     *   * commonName - Optional, for example a first + last name
     *   * status - See the Sabre_CalDAV_SharingPlugin::STATUS_ constants.
     *   * readOnly - boolean
     *   * summary - Optional, a description for the share
     *
     * @param mixed $calendarId
     * @return array
     */
    function getShares($calendarId) {

    }

    /**
     * This method is called when a user replied to a request to share.
     *
     * @param string href The sharee who is replying (often a mailto: address)
     * @param int status One of the SharingPlugin::STATUS_* constants
     * @param string $calendarUri The url to the calendar thats being shared
     * @param string $inReplyTo The unique id this message is a response to
     * @param string $summary A description of the reply
     * @return void
     */
    function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null);

    /**
     * This method marks the calendar as shared, or not.
     *
     * Note that if the calendar is currently shared by people, and false is
     * passed here, all the sharees should be removed.
     *
     * @param mixed $calendarId
     * @param bool $value
     * @return void
     */
    function setSharingEnabled($calendarId, $value);

}
