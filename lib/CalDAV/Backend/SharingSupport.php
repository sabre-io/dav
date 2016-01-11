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
 *
 * Calendar access by sharees
 * ==========================
 *
 * As mentioned earlier, shared calendars must now also be returned for
 * getCalendarsForUser for sharees. A few things change though.
 *
 * The following key must be returned for shared calendars:
 *
 * share-access
 *
 * If the calendar is shared, share-access must be provided and must be one of
 * the Sabre\DAV\Sharing\Plugin::ACCESS_ constants.
 *
 * Only when this is done, the calendar will correctly be marked as a calendar
 * that's shared to him, thus allowing clients to display the correct interface
 * and ACL enforcement.
 *
 * Deleting calendars
 * ==================
 *
 * As an implementor you also need to make sure that deleting calendars
 * behaves as expected.
 *
 * If a sharee deletes their calendar, only their instance of the calendar
 * should be deleted, the original should still exists.
 *
 * Per user-data
 * ============
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
 * Integration with notifications
 * ==============================
 *
 * If the SharingSupport interface is implemented, it's possible to allow
 * people to immediately share calendars with other users.
 *
 * However, in some cases it may be desired to let the invitee first know
 * that someone is trying to share something with them, and allow them to
 * accept or reject the share.
 *
 * If this behavior is desired, it's also required to implement the
 * NotificationSupport interface. Implementing that interface will allow
 * supporting clients to display invitations and let users accept or reject
 * them straight from within their calendaring application.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface SharingSupport extends BackendInterface {

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
     * Publishes a calendar
     *
     * @param mixed $calendarId
     * @param bool $value
     * @return void
     */
    function setPublishStatus($calendarId, $value);

}
