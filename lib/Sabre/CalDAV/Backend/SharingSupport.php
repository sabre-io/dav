<?php

/**
 * Adds support for sharing features to a CalDAV server.
 *
 * A few notes:
 * Implementing NotificationSupport is also required to implement sharing.
 *
 * When a user adds a sharee to the calenars they own, the change is not
 * instant. A 'notification' will first be sent to the recipient, that allows
 * the recipient to either accept or decline the invite.
 *
 * The notification for sharing is represented by
 * Sabre_CalDAV_Notifications_Notification_Invite. When a user responded with
 * an accept or decline, this should also be notified back to the user using
 * Sabre_CalDAV_Notifications_Notification_InviteReply.
 *
 * The unique id that used for Notification_Invite, is the same id that is sent
 * to the inReplyTo argument in the shareReply method.
 *
 * Calendars can be marked as 'shared' or not marked as 'shared'. Even when a
 * calendar does not have any people sharing it, it can still be marked as
 * 'shared'. This status should be sent along from the getCalendarsForUser
 * method. This is done through the following property:
 *
 * {http://sabredav.org/ns}sharing-enabled
 *
 * This property should be either true or false.
 * When a calendar is not marked as shared, but the updateShares method is
 * called, the calendar should be automatically upgraded to a 'shared' calendar
 * and thus the 'sharing-enabled' property should be marked as true.
 *
 * The getCalendarsForUser method should besides a users' own calendars, now
 * also return the calendars that are shared TO him. If a user is not the owner
 * of a calendar, but the calendar is shared TO him, you must also provide the
 * following property:
 *
 * {http://calendarserver.org/ns/}shared-url
 *
 * This property MUST contain the url to the original calendar, that is.. the
 * path to the calendar from the owner.
 *
 * Only when this is done, the calendar will correctly be marked as a calendar
 * that's shared to him, thus allowing clients to display the correct interface
 * and ACL enforcement.
 *
 * If a sharee deletes their calendar, only their instance of the calendar
 * should be deleted, the original should still exists.
 * Pretty much any 'dead' WebDAV properties on these shared calendars should be
 * specific to a user. This means that if the displayname is changed by a
 * sharee, the original is not affected. This is also true for:
 *   * The description
 *   * The color
 *   * The order
 *   * And any other dead properties.
 *
 * Properties like a ctag should not change.
 * Lastly, objects *within* calendars should also have user-specific data. The
 * two things that are user-specific are:
 *   * VALARM objects
 *   * The TRANSP property
 *
 * This _also_ implies that if a VALARM is deleted by a sharee for some event,
 * this has no effect on the original VALARM.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_CalDAV_Backend_SharingSupport extends Sabre_CalDAV_Backend_NotificationSupport {

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
    function updateShares($calendarId, array $add, array $remove);

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
    function getShares($calendarId);

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
