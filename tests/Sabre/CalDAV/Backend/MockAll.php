<?php declare (strict_types=1);

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV\Xml\Notification\NotificationInterface;
use Sabre\DAV;

class MockAll extends Mock implements SchedulingSupport, NotificationSupport, SharingSupport, SubscriptionSupport {

    public $schedulingObjects = [];

    /**
     * Returns a single scheduling object.
     *
     * The returned array should contain the following elements:
     *   * uri - A unique basename for the object. This will be used to
     *           construct a full uri.
     *   * calendardata - The iCalendar object
     *   * lastmodified - The last modification date. Can be an int for a unix
     *                    timestamp, or a PHP DateTime object.
     *   * etag - A unique token that must change if the object changed.
     *   * size - The size of the object, in bytes.
     *
     * @param string $principalUri
     * @param string $objectUri
     * @return array
     */
    function getSchedulingObject($principalUri, $objectUri) {

        if (isset($this->schedulingObjects[$principalUri][$objectUri])) {
            return $this->schedulingObjects[$principalUri][$objectUri];
        }

    }

    /**
     * Returns all scheduling objects for the inbox collection.
     *
     * These objects should be returned as an array. Every item in the array
     * should follow the same structure as returned from getSchedulingObject.
     *
     * The main difference is that 'calendardata' is optional.
     *
     * @param string $principalUri
     * @return array
     */
    function getSchedulingObjects($principalUri) {

        if (isset($this->schedulingObjects[$principalUri])) {
            return array_values($this->schedulingObjects[$principalUri]);
        }
        return [];

    }

    /**
     * Deletes a scheduling object
     *
     * @param string $principalUri
     * @param string $objectUri
     * @return void
     */
    function deleteSchedulingObject($principalUri, $objectUri) {

        if (isset($this->schedulingObjects[$principalUri][$objectUri])) {
            unset($this->schedulingObjects[$principalUri][$objectUri]);
        }

    }

    /**
     * Creates a new scheduling object. This should land in a users' inbox.
     *
     * @param string $principalUri
     * @param string $objectUri
     * @param string $objectData;
     * @return void
     */
    function createSchedulingObject($principalUri, $objectUri, $objectData) {

        if (!isset($this->schedulingObjects[$principalUri])) {
            $this->schedulingObjects[$principalUri] = [];
        }
        $this->schedulingObjects[$principalUri][$objectUri] = [
            'uri'          => $objectUri,
            'calendardata' => $objectData,
            'lastmodified' => null,
            'etag'         => '"' . md5($objectData) . '"',
            'size'         => strlen($objectData)
        ];

    }

    private $shares = [];
    private $notifications;

    function __construct(array $calendars = [], array $calendarData = [], array $notifications = []) {

        parent::__construct($calendars, $calendarData);
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

        $calendars = parent::getCalendarsForUser($principalUri);
        foreach ($calendars as $k => $calendar) {

            if (isset($calendar['share-access'])) {
                continue;
            }
            if (!empty($this->shares[$calendar['id']])) {
                $calendar['share-access'] = DAV\Sharing\Plugin::ACCESS_SHAREDOWNER;
            } else {
                $calendar['share-access'] = DAV\Sharing\Plugin::ACCESS_NOTSHARED;
            }
            $calendars[$k] = $calendar;

        }
        return $calendars;

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
    function getNotificationsForPrincipal($principalUri) {

        if (isset($this->notifications[$principalUri])) {
            return $this->notifications[$principalUri];
        }
        return [];

    }

    /**
     * This deletes a specific notifcation.
     *
     * This may be called by a client once it deems a notification handled.
     *
     * @param string $principalUri
     * @param NotificationInterface $notification
     * @return void
     */
    function deleteNotification($principalUri, NotificationInterface $notification) {

        foreach ($this->notifications[$principalUri] as $key => $value) {
            if ($notification === $value) {
                unset($this->notifications[$principalUri][$key]);
            }
        }

    }

    /**
     * Updates the list of shares.
     *
     * @param mixed $calendarId
     * @param \Sabre\DAV\Xml\Element\Sharee[] $sharees
     * @return void
     */
    function updateInvites($calendarId, array $sharees) {

        if (!isset($this->shares[$calendarId])) {
            $this->shares[$calendarId] = [];
        }

        foreach ($sharees as $sharee) {

            $existingKey = null;
            foreach ($this->shares[$calendarId] as $k => $existingSharee) {
                if ($sharee->href === $existingSharee->href) {
                    $existingKey = $k;
                }
            }
            // Just making sure we're not affecting an existing copy.
            $sharee = clone $sharee;
            if (!$sharee->inviteStatus) {
                $sharee->inviteStatus = DAV\Sharing\Plugin::INVITE_NORESPONSE;
            }

            if ($sharee->access === DAV\Sharing\Plugin::ACCESS_NOACCESS) {
                // It's a removal
                unset($this->shares[$calendarId][$existingKey]);
            } elseif ($existingKey) {
                // It's an update
                $this->shares[$calendarId][$existingKey] = $sharee;
            } else {
                // It's an addition
                $this->shares[$calendarId][] = $sharee;
            }
        }

        // Re-numbering keys
        $this->shares[$calendarId] = array_values($this->shares[$calendarId]);

    }

    /**
     * Returns the list of people whom this calendar is shared with.
     *
     * Every item in the returned list must be a Sharee object with at
     * least the following properties set:
     *   $href
     *   $shareAccess
     *   $inviteStatus
     *
     * and optionally:
     *   $properties
     *
     * @param mixed $calendarId
     * @return \Sabre\DAV\Xml\Element\Sharee[]
     */
    function getInvites($calendarId) {

        if (!isset($this->shares[$calendarId])) {
            return [];
        }

        return $this->shares[$calendarId];

    }

    /**
     * This method is called when a user replied to a request to share.
     *
     * @param string href The sharee who is replying (often a mailto: address)
     * @param int status One of the \Sabre\DAV\Sharing\Plugin::INVITE_* constants
     * @param string $calendarUri The url to the calendar thats being shared
     * @param string $inReplyTo The unique id this message is a response to
     * @param string $summary A description of the reply
     * @return void
     */
    function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null) {

        // This operation basically doesn't do anything yet
        if ($status === DAV\Sharing\Plugin::INVITE_ACCEPTED) {
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
    function setPublishStatus($calendarId, $value) {

        foreach ($this->calendars as $k => $cal) {
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

   /**
     * Subscription list
     *
     * @var array
     */
    protected $subs = [];

    /**
     * Returns a list of subscriptions for a principal.
     *
     * Every subscription is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    subscription. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the subscription.
     *  * principaluri. The owner of the subscription. Almost always the same as
     *    principalUri passed to this method.
     *  * source. Url to the actual feed
     *
     * Furthermore, all the subscription info must be returned too:
     *
     * 1. {DAV:}displayname
     * 2. {http://apple.com/ns/ical/}refreshrate
     * 3. {http://calendarserver.org/ns/}subscribed-strip-todos (omit if todos
     *    should not be stripped).
     * 4. {http://calendarserver.org/ns/}subscribed-strip-alarms (omit if alarms
     *    should not be stripped).
     * 5. {http://calendarserver.org/ns/}subscribed-strip-attachments (omit if
     *    attachments should not be stripped).
     * 7. {http://apple.com/ns/ical/}calendar-color
     * 8. {http://apple.com/ns/ical/}calendar-order
     *
     * @param string $principalUri
     * @return array
     */
    function getSubscriptionsForUser($principalUri) {

        if (isset($this->subs[$principalUri])) {
            return $this->subs[$principalUri];
        }
        return [];

    }

    /**
     * Creates a new subscription for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this subscription in other methods, such as updateSubscription.
     *
     * @param string $principalUri
     * @param string $uri
     * @param array $properties
     * @return mixed
     */
    function createSubscription($principalUri, $uri, array $properties) {

        $properties['uri'] = $uri;
        $properties['principaluri'] = $principalUri;
        $properties['source'] = $properties['{http://calendarserver.org/ns/}source']->getHref();

        if (!isset($this->subs[$principalUri])) {
            $this->subs[$principalUri] = [];
        }

        $id = [$principalUri, count($this->subs[$principalUri]) + 1];

        $properties['id'] = $id;

        $this->subs[$principalUri][] = array_merge($properties, [
            'id' => $id,
        ]);

        return $id;

    }

    /**
     * Updates a subscription
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param mixed $subscriptionId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updateSubscription($subscriptionId, DAV\PropPatch $propPatch) {

        $found = null;
        foreach ($this->subs[$subscriptionId[0]] as &$sub) {

            if ($sub['id'][1] === $subscriptionId[1]) {
                $found = & $sub;
                break;
            }

        }

        if (!$found) return;

        $propPatch->handleRemaining(function($mutations) use (&$found) {
            foreach ($mutations as $k => $v) {
                $found[$k] = $v;
            }
            return true;
        });

    }

    /**
     * Deletes a subscription
     *
     * @param mixed $subscriptionId
     * @return void
     */
    function deleteSubscription($subscriptionId) {

        foreach ($this->subs[$subscriptionId[0]] as $index => $sub) {

            if ($sub['id'][1] === $subscriptionId[1]) {
                unset($this->subs[$subscriptionId[0]][$index]);
                return true;
            }

        }

        return false;

    }
}
