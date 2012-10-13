<?php

namespace Sabre\CalDAV\Backend;
use Sabre\DAV;
use Sabre\CalDAV;

class Mock extends AbstractBackend implements NotificationSupport, SharingSupport {

    private $calendarData;
    private $calendars;
    private $notifications;
    private $shares = array();

    function __construct(array $calendars, array $calendarData, array $notifications = array()) {

        $this->calendars = $calendars;
        $this->calendarData = $calendarData;
        $this->notifications = $notifications;

    }

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri, which the basename of the uri with which the calendar is
     *    accessed.
     *  * principalUri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * @param string $principalUri
     * @return array
     */
    function getCalendarsForUser($principalUri) {

        $r = array();
        foreach($this->calendars as $row) {
            if ($row['principaluri'] == $principalUri) {
                $r[] = $row;
            }
        }

        return $r;

    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar.
     *
     * This function must return a server-wide unique id that can be used
     * later to reference the calendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return string|int
     */
    function createCalendar($principalUri,$calendarUri,array $properties) {

        $id = DAV\UUIDUtil::getUUID();
        $this->calendars[] = array_merge(array(
            'id' => $id,
            'principaluri' => $principalUri,
            'uri' => $calendarUri,
            '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT','VTODO')),
        ), $properties);

        return $id;

    }

    /**
     * Updates properties on this node,
     *
     * The properties array uses the propertyName in clark-notation as key,
     * and the array value for the property value. In the case a property
     * should be deleted, the property value will be null.
     *
     * This method must be atomic. If one property cannot be changed, the
     * entire operation must fail.
     *
     * If the operation was successful, true can be returned.
     * If the operation failed, false can be returned.
     *
     * Deletion of a non-existent property is always successful.
     *
     * Lastly, it is optional to return detailed information about any
     * failures. In this case an array should be returned with the following
     * structure:
     *
     * array(
     *   403 => array(
     *      '{DAV:}displayname' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}owner' => null,
     *   )
     * )
     *
     * In this example it was forbidden to update {DAV:}displayname.
     * (403 Forbidden), which in turn also caused {DAV:}owner to fail
     * (424 Failed Dependency) because the request needs to be atomic.
     *
     * @param string $calendarId
     * @param array $properties
     * @return bool|array
     */
    public function updateCalendar($calendarId, array $properties) {

        return false;

    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param string $calendarId
     * @return void
     */
    public function deleteCalendar($calendarId) {

        foreach($this->calendars as $k=>$calendar) {
            if ($calendar['id'] === $calendarId) {
                unset($this->calendars[$k]);
            }
        }

    }

    /**
     * Returns all calendar objects within a calendar object.
     *
     * Every item contains an array with the following keys:
     *   * id - unique identifier which will be used for subsequent updates
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can be any arbitrary string.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * calendarid - The calendarid as it was passed to this function.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * @param string $calendarId
     * @return array
     */
    public function getCalendarObjects($calendarId) {

        if (!isset($this->calendarData[$calendarId]))
            return array();

        $objects = $this->calendarData[$calendarId];

        foreach($objects as $uri => &$object) {
            $object['calendarid'] = $calendarId;
            $object['uri'] = $uri;

        }
        return $objects;

    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @return array
     */
    function getCalendarObject($calendarId,$objectUri) {

        if (!isset($this->calendarData[$calendarId][$objectUri])) {
            throw new DAV\Exception\NotFound('Object could not be found');
        }
        $object = $this->calendarData[$calendarId][$objectUri];
        $object['calendarid'] = $calendarId;
        $object['uri'] = $objectUri;
        return $object;

    }

    /**
     * Creates a new calendar object.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return void
     */
    function createCalendarObject($calendarId,$objectUri,$calendarData) {

        $this->calendarData[$calendarId][$objectUri] = array(
            'calendardata' => $calendarData,
            'calendarid' => $calendarId,
            'uri' => $objectUri,
        );

    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return void
     */
    function updateCalendarObject($calendarId,$objectUri,$calendarData) {

        $this->calendarData[$calendarId][$objectUri] = array(
            'calendardata' => $calendarData,
            'calendarid' => $calendarId,
            'uri' => $objectUri,
        );

    }

    /**
     * Deletes an existing calendar object.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @return void
     */
    function deleteCalendarObject($calendarId,$objectUri) {

        throw new Exception('Not implemented');


    }

    /**
     * Returns a list of notifications for a given principal url.
     *
     * The returned array should only consist of implementations of
     * Sabre\CalDAV\Notifications\INotificationType.
     *
     * @param string $principalUri
     * @return array
     */
    public function getNotificationsForPrincipal($principalUri) {

        if (isset($this->notifications[$principalUri])) {
            return $this->notifications[$principalUri];
        }
        return array();

    }

    /**
     * This deletes a specific notifcation.
     *
     * This may be called by a client once it deems a notification handled.
     *
     * @param string $principalUri
     * @param Sabre\CalDAV\Notifications\INotificationType $notification
     * @return void
     */
    public function deleteNotification($principalUri, CalDAV\Notifications\INotificationType $notification) {

        foreach($this->notifications[$principalUri] as $key=>$value) {
            if ($notification === $value) {
                unset($this->notifications[$principalUri][$key]);
            }
        }

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
    public function updateShares($calendarId, array $add, array $remove) {

        if (!isset($this->shares[$calendarId])) {
            $this->shares[$calendarId] = array();
        }

        foreach($add as $val) {
            $val['status'] = CalDAV\SharingPlugin::STATUS_NORESPONSE;
            $this->shares[$calendarId][] = $val;
        }

        foreach($this->shares[$calendarId] as $k=>$share) {

            if (in_array($share['href'], $remove)) {
                unset($this->shares[$calendarId][$k]);
            }

        }

        // Re-numbering keys
        $this->shares[$calendarId] = array_values($this->shares[$calendarId]);

    }

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
     * @param mixed $calendarId
     * @return array
     */
    public function getShares($calendarId) {

        if (!isset($this->shares[$calendarId])) {
            return array();
        }

        return $this->shares[$calendarId];

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
    public function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null) {

        // This operation basically doesn't do anything yet
        if ($status === CalDAV\SharingPlugin::STATUS_ACCEPTED) {
            return 'calendars/blabla/calendar';
        }

    }

    /**
     * Publishes a calendar
     *
     * @param mixed $calendarId
     * @param bool $value
     * @return void
     */
    public function setPublishStatus($calendarId, $value) {

        foreach($this->calendars as $k=>$cal) {
            if ($cal['id'] === $calendarId) {
                if (!$value) {
                    unset($cal['{http://calendarserver.org/ns/}publish-url']);
                } else {
                    $cal['{http://calendarserver.org/ns/}publish-url'] = 'http://example.org/public/ ' . $calendarId . '.ics';
                }
                return;
            }
        }

        throw new DAV\Exception('Calendar with id "' . $calendarId . '" not found');

    }

}
