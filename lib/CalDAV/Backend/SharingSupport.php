<?php

namespace Sabre\CalDAV\Backend;

/**
 * Adds support for sharing features to a CalDAV server.
 *
 * Note: This feature is experimental, and may change in between different
 * SabreDAV versions.
 *
 * https://trac.calendarserver.org/browser/CalendarServer/trunk/doc/Extensions/caldav-sharing.txt
 *
 * An overview
 * ===========
 *
 * Implementing this interface will allow a user to share his or her calendars
 * to other users. Effectively, when a calendar is shared the calendar will
 * show up in both the Sharer's and Sharee's calendar-home root.
 * This interface adds a few methods that ensure that this happens, and there
 * are also a number of new requirements in the base-class you must now follow.
 *
 *
 * How it works
 * ============
 *
 * When a user shares a calendar, the updateShares() method will be called with
 * a list of sharees that are now added, and a list of sharees that have been
 * removed.
 * Removal is instant, but when a sharee is added the sharee first gets a
 * chance to accept or reject the invitation for a share.
 *
 * After a share is accepted, the calendar will be returned from
 * getUserCalendars for both the sharer, and the sharee.
 *
 * If the sharee deletes the calendar, only their share gets deleted. When the
 * owner deletes a calendar, it will be removed for everybody.
 *
 *
 * Notifications
 * =============
 *
 * During all these sharing operations, a lot of notifications are sent back
 * and forward.
 *
 * Whenever the list of sharees for a calendar has been changed (they have been
 * added, removed or modified) all sharees should get a notification for this
 * change.
 * This notification is always represented by:
 *
 * Sabre\CalDAV\Xml\Notification\Invite
 *
 * In the case of an invite, the sharee may reply with an 'accept' or
 * 'decline'. These are always represented by:
 *
 * Sabre\CalDAV\Xml\Notification\InviteReply
 *
 *
 * Calendar access by sharees
 * ==========================
 *
 * As mentioned earlier, shared calendars must now also be returned for
 * getCalendarsForUser for sharees. A few things change though.
 *
 * The following properties must be specified:
 *
 * 1. owner-principal
 *
 * If the calendar is shared, and the current user is not the owner, then this
 * property MUST contain information to identify the real owner.
 *
 * 2. read-only
 *
 * This should be either 0 or 1, depending on if the user has read-only or
 * read-write access to the calendar.
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
 * Properties like a ctag should not be different for multiple instances of the
 * calendar.
 *
 * Lastly, objects *within* calendars should also have user-specific data. The
 * two things that are user-specific are:
 *   * VALARM objects
 *   * The TRANSP property
 *
 * This _also_ implies that if a VALARM is deleted by a sharee for some event,
 * this has no effect on the original VALARM.
 *
 * Understandably, the this last requirement is one of the hardest.
 * Realisticly, I can see people ignoring this part of the spec, but that could
 * cause a different set of issues.
 *
 *
 * Publishing
 * ==========
 *
 * When a user publishes a url, the server should generate a 'publish url'.
 * This is a read-only url, anybody can use to consume the calendar feed.
 *
 * Calendars are in one of two states:
 *   * published
 *   * unpublished
 *
 * If a calendar is published, the following property should be returned
 * for each calendar in getCalendarsForUser.
 *
 * {http://calendarserver.org/ns/}publish-url
 *
 * This element should contain a {DAV:}href element, which points to the
 * public url that does not require authentication. Unlike every other href,
 * this url must be absolute.
 *
 * Ideally, the following property is always returned
 *
 * {http://calendarserver.org/ns/}pre-publish-url
 *
 * This property should contain the url that the calendar _would_ have, if it
 * were to be published. iCal uses this to display the url, before the user
 * will actually publish it.
 *
 *
 * Selectively disabling publish or share feature
 * ==============================================
 *
 * If Sabre\CalDAV\Property\AllowedSharingModes is returned from
 * getCalendarsForUser, this allows the server to specify whether either sharing,
 * or publishing is supported.
 *
 * This allows a client to determine in advance which features are available,
 * and update the interface appropriately. If this property is not returned by
 * the backend, the SharingPlugin automatically injects it and assumes both
 * features are available.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface SharingSupport extends NotificationSupport {

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
     *   * status - See the Sabre\CalDAV\SharingPlugin::STATUS_ constants.
     *   * readOnly - boolean
     *   * summary - Optional, a description for the share
     *
     * This method may be called by either the original instance of the
     * calendar, as well as the shared instances. In the case of the shared
     * instances, it is perfectly acceptable to return an empty array in case
     * there are privacy concerns.
     *
     * @param mixed $calendarId
     * @return array
     */
    function getShares($calendarId);

    /**
     * This method is called when a user replied to a request to share.
     *
     * If the user chose to accept the share, this method should return the
     * newly created calendar url.
     *
     * @param string href The sharee who is replying (often a mailto: address)
     * @param int status One of the SharingPlugin::STATUS_* constants
     * @param string $calendarUri The url to the calendar thats being shared
     * @param string $inReplyTo The unique id this message is a response to
     * @param string $summary A description of the reply
     * @return null|string
     */
    function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null);

    /**
     * Publishes a calendar
     *
     * @param mixed $calendarId
     * @param bool $value
     * @return void
     */
    function setPublishStatus($calendarId, $value);

}
