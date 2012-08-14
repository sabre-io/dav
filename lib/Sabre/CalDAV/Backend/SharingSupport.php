<?php

/**
 * Adds support for sharing features to a CalDAV server.
 *
 * Early warning: Currently SabreDAV provides no implementation for this. This
 * is, because in it's current state there is no elegant way to do this.
 * The problem lies in the fact that a real CalDAV server with sharing support
 * would first need email support (with invite notifications), and really also
 * a browser-frontend that allows people to accept or reject these shares.
 *
 * In addition, the CalDAV backends are currently kept as independent as
 * possible, and should not be aware of principals, email addresses or
 * accounts.
 *
 * Adding an implementation for Sharing to standard-sabredav would contradict
 * these goals, so for this reason this is currently not implemented, although
 * it may very well in the future; but probably not before SabreDAV 2.0.
 *
 * The interface works however, so if you implement all this, and do it
 * correctly sharing _will_ work. It's not particularly easy, and I _urge you_
 * to make yourself acquainted with the following document first:
 *
 * https://trac.calendarserver.org/browser/CalendarServer/trunk/doc/Extensions/caldav-sharing-02.txt
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
 * How it works
 * ============
 *
 * When a user shares a calendar, the addShare() method will be called with a
 * list of sharees that are now added, and a list of sharees that have been
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
 * Marking a calendar as shared
 * ============================
 *
 * Calendars can be marked as 'shared' and also not as 'shared'. Even when a
 * calendar does not have any sharees, it can still be marked as shared. This
 * is a (silly) detail from the spec, but it is important.
 * When the status of a calendar (shared or non-shared) is set, the
 * setSharingEnabled() method in this interface is called.
 *
 * When a calendar already has sharees, and sharing is subsequently disabled,
 * the effect is 'as if all the sharees were removed'.
 *
 * If a calendar is marked as shared, this should be returned from
 * getCalendarsForUser. Specifically, the following property should be set to
 * true for these calendars:
 *
 * {http://sabredav.org/ns}sharing-enabled
 *
 * If a calendar is not 'enabled for sharing', but sharees are added, the
 * calendar should be automatically upgraded to a shared calendar.
 *
 * Note that this sharing-enabled property is _only_ returned for the owner of
 * the calendar, and not sharees.
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
 * Sabre_CalDAV_Notifications_Notification_Invite
 *
 * In the case of an invite, the sharee may reply with an 'accept' or
 * 'decline'. These are always represented by:
 *
 * Sabre_CalDAV_Notifications_Notification_Invite
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
 * 1. {http://calendarserver.org/ns/}shared-url
 *
 * This property MUST contain the url to the original calendar, that is.. the
 * path to the calendar from the owner.
 *
 * 2. {http://sabredav.org/ns}owner-principal
 *
 * This is a url to to the principal who is sharing the calendar.
 *
 * 3. {http://sabredav.org/ns}read-only
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
 * Publishing Calendars
 * ====================
 *
 * Aside from sharing with different users, it's also possible for a user to
 * mark a calendar as published.
 *
 * When a calendar gets published, a new calendar feed should be created on a
 * different url, where everybody can subscribe to the url, using a 'subscribe'
 * feature often found in calendar software.
 *
 * Publishing is a lot easier, because there's no need for notifications or
 * invites.
 *
 * If a user 'unshares' the calendar, publishing should be stopped as well. If
 * a user starts publishing the calendar, the calendar should be marked as
 * 'shared' as well.
 *
 * If a calendar is published, the following property should be returned:
 *
 * {http://calendarserver.org/ns/}publish-url
 *
 * This element should contain a {DAV:}href element, which points to the
 * public url that does not require authentication.
 *
 * Note that even though there's a usecase for a calendar to be both shared and
 * published, iCal at least does not seem to provide this ability. So it's
 * assumed that a calendar can only ever be not-shared, shared or published,
 * but never a combination.
 *
 * The implication is that only owners of calendars can publish calendars.
 * Sharees can not.
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

    /**
     * Publishes a calendar
     *
     * @param mixed $calendarId
     * @return void
     */
    function publishCalendar($calendarId);

}
