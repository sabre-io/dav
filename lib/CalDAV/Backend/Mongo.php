<?php

namespace Sabre\CalDAV\Backend;

use Sabre\VObject;

/**
 * Mongo CalDAV backend.
 *
 * This backend is used to store calendar-data in a MongoDb database
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Linagora Folks (lgs-openpaas-dev@linagora.com)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Mongo extends \Sabre\CalDAV\Backend\AbstractBackend implements \Sabre\CalDAV\Backend\SubscriptionSupport, \Sabre\CalDAV\Backend\SyncSupport, \Sabre\CalDAV\Backend\SchedulingSupport, \Sabre\CalDAV\Backend\SharingSupport
{
    /**
     * We need to specify a max date, because we need to stop *somewhere*.
     *
     * On 32 bit system the maximum for a signed integer is 2147483647, so
     * MAX_DATE cannot be higher than date('Y-m-d', 2147483647) which results
     * in 2038-01-19 to avoid problems when the date is converted
     * to a unix timestamp.
     */
    const MAX_DATE = '2038-01-01';

    /**
     * MongoDb.
     *
     * @var \MongoDB\Database
     */
    protected $db;

    /**
     * The collection name that will be used for calendars.
     *
     * @var string
     */
    public $calendarCollectionName = 'calendars';

    /**
     * The collection name that will be used for calendars instances.
     *
     * A single calendar can have multiple instances, if the calendar is
     * shared.
     *
     * @var string
     */
    public $calendarInstancesCollectionName = 'calendarinstances';

    /**
     * The collection name that will be used for calendar objects.
     *
     * @var string
     */
    public $calendarObjectCollectionName = 'calendarobjects';

    /**
     * The collection name that will be used for tracking changes in calendars.
     *
     * @var string
     */
    public $calendarChangesCollectionName = 'calendarchanges';

    /**
     * The collection name that will be used inbox items.
     *
     * @var string
     */
    public $schedulingObjectCollectionName = 'schedulingobjects';

    /**
     * The collection name that will be used for calendar subscriptions.
     *
     * @var string
     */
    public $calendarSubscriptionsCollectionName = 'calendarsubscriptions';

    /**
     * List of CalDAV properties, and how they map to database document properties
     * Add your own properties by simply adding on to this array.
     *
     * Note that only string-based properties are supported here.
     *
     * @var array
     */
    public $propertyMap = [
        '{DAV:}displayname' => 'displayname',
        '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
        '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => 'timezone',
        '{http://apple.com/ns/ical/}calendar-order' => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color' => 'calendarcolor',
    ];

    /**
     * List of subscription properties, and how they map to database document properties.
     *
     * @var array
     */
    public $subscriptionPropertyMap = [
        '{DAV:}displayname' => 'displayname',
        '{http://apple.com/ns/ical/}refreshrate' => 'refreshrate',
        '{http://apple.com/ns/ical/}calendar-order' => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color' => 'calendarcolor',
        '{http://calendarserver.org/ns/}subscribed-strip-todos' => 'striptodos',
        '{http://calendarserver.org/ns/}subscribed-strip-alarms' => 'stripalarms',
        '{http://calendarserver.org/ns/}subscribed-strip-attachments' => 'stripattachments',
    ];

    /**
     * Creates the backend.
     *
     * @param \MongoDB\Database $db
     */
    public function __construct(\MongoDB\Database $db)
    {
        $this->db = $db;
    }

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the calendar.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * Many clients also require:
     * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     * For this property, you can just return an instance of
     * Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet.
     *
     * If you return {http://sabredav.org/ns}read-only and set the value to 1,
     * ACL will automatically be put in read-only mode.
     *
     * @param string $principalUri
     *
     * @return array
     */
    public function getCalendarsForUser($principalUri)
    {
        $fields = array_values($this->propertyMap);
        $fields[] = 'calendarid';
        $fields[] = 'uri';
        $fields[] = 'synctoken';
        $fields[] = 'components';
        $fields[] = 'principaluri';
        $fields[] = 'transparent';
        $fields[] = 'access';
        $fields[] = 'share_invitestatus';

        $collection = $this->db->selectCollection($this->calendarInstancesCollectionName);

        $query = ['principaluri' => $principalUri];
        $projection = array_fill_keys($fields, 1);
        $options = [
            'projection' => $projection,
            'sort' => ['calendarorder' => 1],
        ];

        $res = $collection->find($query, $options);

        $calendarInstances = [];
        $calendarIds = [];

        foreach ($res as $row) {
            $calendarIds[] = new \MongoDB\BSON\ObjectId((string) $row['calendarid']);

            $calendarInstances[] = $row;
        }

        $collection = $this->db->selectCollection($this->calendarCollectionName);
        $query = ['_id' => ['$in' => $calendarIds]];
        $projection = [
            '_id' => 1,
            'synctoken' => 1,
            'components' => 1,
        ];
        $result = $collection->find($query, ['projection' => $projection]);

        $calendars = [];

        foreach ($result as $row) {
            $calendars[(string) $row['_id']] = $row;
        }

        $userCalendars = [];
        foreach ($calendarInstances as $calendarInstance) {
            $currentCalendarId = (string) $calendarInstance['calendarid'];

            if (!isset($calendars[$currentCalendarId])) {
                $this->server->getLogger()->error(
                    'No matching calendar found',
                    'Calendar '.$currentCalendarId.' not found for calendar instance '.(string) $calendarInstance['_id']
                );

                continue;
            }

            $calendar = $calendars[$currentCalendarId];

            $components = (array) $calendar['components'];

            $userCalendar = [
                'id' => [(string) $calendarInstance['calendarid'], (string) $calendarInstance['_id']],
                'uri' => $calendarInstance['uri'],
                'principaluri' => $calendarInstance['principaluri'],
                '{'.\Sabre\CalDAV\Plugin::NS_CALENDARSERVER.'}getctag' => 'http://sabre.io/ns/sync/'.($calendar['synctoken'] ? $calendar['synctoken'] : '0'),
                '{http://sabredav.org/ns}sync-token' => $calendar['synctoken'] ? $calendar['synctoken'] : '0',
                '{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet($components),
                '{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}schedule-calendar-transp' => new \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp($calendarInstance['transparent'] ? 'transparent' : 'opaque'),
                'share-resource-uri' => '/ns/share/'.$calendarInstance['_id'],
            ];

            $userCalendar['share-access'] = (int) $calendarInstance['access'];
            // 1 = owner, 2 = readonly, 3 = readwrite
            if ($calendarInstance['access'] > 1) {
                // read-only is for backwards compatbility. Might go away in the future.
                $userCalendar['read-only'] = \Sabre\DAV\Sharing\Plugin::ACCESS_READ === (int) $calendarInstance['access'];
            }

            foreach ($this->propertyMap as $xmlName => $dbName) {
                $userCalendar[$xmlName] = $calendarInstance[$dbName];
            }

            $userCalendars[] = $userCalendar;
        }

        return $userCalendars;
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used
     * to reference this calendar in other methods, such as updateCalendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array  $properties
     *
     * @return string
     */
    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        $sccs = '{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}supported-calendar-component-set';

        // Insert in calendars collection
        $obj = [
          'synctoken' => 1,
        ];
        if (!isset($properties[$sccs])) {
            // Default value
            $obj['components'] = ['VEVENT', 'VTODO'];
        } else {
            if (!($properties[$sccs] instanceof \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet)) {
                throw new \Sabre\DAV\Exception('The '.$sccs.' property must be of type: \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet');
            }
            $obj['components'] = $properties[$sccs]->getValue();
        }

        $collection = $this->db->selectCollection($this->calendarCollectionName);
        $insertResult = $collection->insertOne($obj);
        $calendarId = (string) $insertResult->getInsertedId();

        // Insert in calendarinstances collection
        $obj = [
            'principaluri' => $principalUri,
            'uri' => $calendarUri,
            'transparent' => 0,
            'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER,
            'share_invitestatus' => \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED,
            'calendarid' => new \MongoDB\BSON\ObjectId($calendarId),
        ];

        $transp = '{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}schedule-calendar-transp';
        if (isset($properties[$transp])) {
            $obj['transparent'] = 'transparent' === $properties[$transp]->getValue();
        }
        foreach ($this->propertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {
                $obj[$dbName] = $properties[$xmlName];
            } else {
                $obj[$dbName] = null;
            }
        }

        $collection = $this->db->selectCollection($this->calendarInstancesCollectionName);
        $insertResult = $collection->insertOne($obj);

        return [$calendarId, (string) $insertResult->getInsertedId()];
    }

    /**
     * Updates properties for a calendar.
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
     * @param mixed                $calendarId
     * @param \Sabre\DAV\PropPatch $propPatch
     */
    public function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch)
    {
        $this->_assertIsArray($calendarId);

        list($calendarId, $instanceId) = $calendarId;

        $supportedProperties = array_keys($this->propertyMap);
        $supportedProperties[] = '{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}schedule-calendar-transp';

        $propPatch->handle($supportedProperties, function ($mutations) use ($calendarId, $instanceId) {
            $newValues = [];
            foreach ($mutations as $propertyName => $propertyValue) {
                switch ($propertyName) {
                    case '{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}schedule-calendar-transp':
                        $fieldName = 'transparent';
                        $newValues[$fieldName] = 'transparent' === $propertyValue->getValue();
                        break;
                    default:
                        $fieldName = $this->propertyMap[$propertyName];
                        $newValues[$fieldName] = $propertyValue;
                        break;
                }
            }

            $collection = $this->db->selectCollection($this->calendarInstancesCollectionName);
            $query = ['_id' => new \MongoDB\BSON\ObjectId($instanceId)];
            $collection->updateOne($query, ['$set' => $newValues]);
            $this->addChange($calendarId, '', 2);

            return true;
        });
    }

    /**
     * Delete a calendar and all it's objects.
     *
     * @param mixed $calendarId
     */
    public function deleteCalendar($calendarId)
    {
        $this->_assertIsArray($calendarId);

        list($calendarId, $instanceId) = $calendarId;
        $mongoId = new \MongoDB\BSON\ObjectId($calendarId);
        $mongoInstanceId = new \MongoDB\BSON\ObjectId($instanceId);

        $collection = $this->db->selectCollection($this->calendarInstancesCollectionName);
        $query = ['_id' => $mongoInstanceId];
        $row = $collection->findOne($query);

        if (\Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER === (int) $row['access']) {
            /**
             * If the user is the owner of the calendar, we delete all data and all
             * instances.
             **/
            $collection = $this->db->selectCollection($this->calendarObjectCollectionName);
            $collection->deleteMany(['calendarid' => $mongoId]);

            $collection = $this->db->selectCollection($this->calendarChangesCollectionName);
            $collection->deleteMany(['calendarid' => $mongoId]);

            $collection = $this->db->selectCollection($this->calendarInstancesCollectionName);
            $collection->deleteMany(['calendarid' => $mongoId]);

            $collection = $this->db->selectCollection($this->calendarCollectionName);
            $collection->deleteMany(['_id' => $mongoId]);
        } else {
            /**
             * If it was an instance of a shared calendar, we only delete that
             * instance.
             */
            $collection = $this->db->selectCollection($this->calendarInstancesCollectionName);
            $collection->deleteMany(['_id' => $mongoInstanceId]);
        }
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can
     *     be any arbitrary string, but making sure it ends with '.ics' is a
     *     good idea. This is only the basename, or filename, not the full
     *     path.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * size - The size of the calendar objects, in bytes.
     *   * component - optional, a string containing the type of object, such
     *     as 'vevent' or 'vtodo'. If specified, this will be used to populate
     *     the Content-Type header.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @param mixed $calendarId
     *
     * @return array
     */
    public function getCalendarObjects($calendarId)
    {
        $this->_assertIsArray($calendarId);

        $calendarId = $calendarId[0];

        $query = ['calendarid' => $calendarId];
        $projection = [
            '_id' => 1,
            'uri' => 1,
            'lastmodified' => 1,
            'etag' => 1,
            'calendarid' => 1,
            'size' => 1,
            'componenttype' => 1,
        ];
        $collection = $this->db->selectCollection($this->calendarObjectCollectionName);

        $result = [];
        foreach ($collection->find($query, ['projection' => $projection]) as $row) {
            $result[] = [
                'id' => (string) $row['_id'],
                'uri' => $row['uri'],
                'lastmodified' => $row['lastmodified'],
                'etag' => '"'.$row['etag'].'"',
                'size' => (int) $row['size'],
                'component' => strtolower($row['componenttype']),
            ];
        }

        return $result;
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     *
     * @return array|null
     */
    public function getCalendarObject($calendarId, $objectUri)
    {
        $result = $this->getMultipleCalendarObjects($calendarId, [$objectUri]);

        return array_shift($result);
    }

    /**
     * Returns a list of calendar objects.
     *
     * This method should work identical to getCalendarObject, but instead
     * return all the calendar objects in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $calendarId
     * @param array $uris
     *
     * @return array
     */
    public function getMultipleCalendarObjects($calendarId, array $uris)
    {
        $this->_assertIsArray($calendarId);

        $calendarId = $calendarId[0];

        $query = ['calendarid' => $calendarId, 'uri' => ['$in' => $uris]];
        $projection = [
            '_id' => 1,
            'uri' => 1,
            'lastmodified' => 1,
            'etag' => 1,
            'calendarid' => 1,
            'size' => 1,
            'calendardata' => 1,
            'componenttype' => 1,
        ];
        $collection = $this->db->selectCollection($this->calendarObjectCollectionName);

        $result = [];
        foreach ($collection->find($query, ['projection' => $projection]) as $row) {
            $result[] = [
                'id' => (string) $row['_id'],
                'uri' => $row['uri'],
                'lastmodified' => $row['lastmodified'],
                'etag' => '"'.$row['etag'].'"',
                'size' => (int) $row['size'],
                'calendardata' => $row['calendardata'],
                'component' => strtolower($row['componenttype']),
            ];
        }

        return $result;
    }

    /**
     * Creates a new calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     * @param string $calendarData
     *
     * @return string|null
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $this->_assertIsArray($calendarId);

        $calendarId = $calendarId[0];

        $extraData = $this->getDenormalizedData($calendarData);

        $collection = $this->db->selectCollection($this->calendarObjectCollectionName);
        $obj = [
            'calendarid' => $calendarId,
            'uri' => $objectUri,
            'calendardata' => $calendarData,
            'lastmodified' => time(),
            'etag' => $extraData['etag'],
            'size' => $extraData['size'],
            'componenttype' => $extraData['componentType'],
            'firstoccurence' => $extraData['firstOccurence'],
            'lastoccurence' => $extraData['lastOccurence'],
            'uid' => $extraData['uid'],
        ];
        $collection->insertOne($obj);
        $this->addChange($calendarId, $objectUri, 1);

        return '"'.$extraData['etag'].'"';
    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     * @param string $calendarData
     *
     * @return string|null
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $this->_assertIsArray($calendarId);

        $calendarId = $calendarId[0];

        $extraData = $this->getDenormalizedData($calendarData);
        $collection = $this->db->selectCollection($this->calendarObjectCollectionName);

        $query = ['calendarid' => $calendarId, 'uri' => $objectUri];
        $obj = ['$set' => [
            'calendardata' => $calendarData,
            'lastmodified' => time(),
            'etag' => $extraData['etag'],
            'size' => $extraData['size'],
            'componenttype' => $extraData['componentType'],
            'firstoccurence' => $extraData['firstOccurence'],
            'lastoccurence' => $extraData['lastOccurence'],
            'uid' => $extraData['uid'],
        ]];

        $collection->updateMany($query, $obj);
        $this->addChange($calendarId, $objectUri, 2);

        return '"'.$extraData['etag'].'"';
    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     */
    public function deleteCalendarObject($calendarId, $objectUri)
    {
        $this->_assertIsArray($calendarId);

        $calendarId = $calendarId[0];

        $collection = $this->db->selectCollection($this->calendarObjectCollectionName);
        $query = ['calendarid' => $calendarId, 'uri' => $objectUri];
        $collection->deleteMany($query);
        $this->addChange($calendarId, $objectUri, 3);
    }

    /**
     * Performs a calendar-query on the contents of this calendar.
     *
     * The calendar-query is defined in RFC4791 : CalDAV. Using the
     * calendar-query it is possible for a client to request a specific set of
     * object, based on contents of iCalendar properties, date-ranges and
     * iCalendar component types (VTODO, VEVENT).
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
     * The list of filters are specified as an array. The exact array is
     * documented by \Sabre\CalDAV\CalendarQueryParser.
     *
     * Note that it is extremely likely that getCalendarObject for every path
     * returned from this method will be called almost immediately after. You
     * may want to anticipate this to speed up these requests.
     *
     * This method provides a default implementation, which parses *all* the
     * iCalendar objects in the specified calendar.
     *
     * This default may well be good enough for personal use, and calendars
     * that aren't very large. But if you anticipate high usage, big calendars
     * or high loads, you are strongly adviced to optimize certain paths.
     *
     * The best way to do so is override this method and to optimize
     * specifically for 'common filters'.
     *
     * Requests that are extremely common are:
     *   * requests for just VEVENTS
     *   * requests for just VTODO
     *   * requests with a time-range-filter on a VEVENT.
     *
     * ..and combinations of these requests. It may not be worth it to try to
     * handle every possible situation and just rely on the (relatively
     * easy to use) CalendarQueryValidator to handle the rest.
     *
     * Note that especially time-range-filters may be difficult to parse. A
     * time-range filter specified on a VEVENT must for instance also handle
     * recurrence rules correctly.
     * A good example of how to interpret all these filters can also simply
     * be found in \Sabre\CalDAV\CalendarQueryFilter. This class is as correct
     * as possible, so it gives you a good idea on what type of stuff you need
     * to think of.
     *
     * This specific implementation (for the PDO) backend optimizes filters on
     * specific components, and VEVENT time-ranges.
     *
     * @param mixed $calendarId
     * @param array $filters
     *
     * @return array
     */
    public function calendarQuery($calendarId, array $filters)
    {
        $this->_assertIsArray($calendarId);

        $calendarId = $calendarId[0];

        $componentType = null;
        $requirePostFilter = true;
        $timeRange = null;

        // if no filters were specified, we don't need to filter after a query
        if (!$filters['prop-filters'] && !$filters['comp-filters']) {
            $requirePostFilter = false;
        }

        // Figuring out if there's a component filter
        if (count($filters['comp-filters']) > 0 && !$filters['comp-filters'][0]['is-not-defined']) {
            $componentType = $filters['comp-filters'][0]['name'];

            // Checking if we need post-filters
            if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['time-range'] && !$filters['comp-filters'][0]['prop-filters']) {
                $requirePostFilter = false;
            }
            // There was a time-range filter
            if ('VEVENT' == $componentType && isset($filters['comp-filters'][0]['time-range'])) {
                $timeRange = $filters['comp-filters'][0]['time-range'];

                // If start time OR the end time is not specified, we can do a 100% accurate query.
                if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['prop-filters'] && (!$timeRange['start'] || !$timeRange['end'])) {
                    $requirePostFilter = false;
                }
            }
        }

        if ($requirePostFilter) {
            $projection = ['uri' => 1, 'calendardata' => 1];
        } else {
            $projection = ['uri' => 1];
        }
        $collection = $this->db->selectCollection($this->calendarObjectCollectionName);
        $query = ['calendarid' => $calendarId];

        if ($componentType) {
            $query['componenttype'] = $componentType;
        }

        if ($timeRange && $timeRange['start']) {
            $query['lastoccurence'] = ['$gte' => $timeRange['start']->getTimeStamp()];
        }
        if ($timeRange && $timeRange['end']) {
            $query['firstoccurence'] = ['$lt' => $timeRange['end']->getTimeStamp()];
        }

        $result = [];
        foreach ($collection->find($query, ['projection' => $projection]) as $row) {
            if ($requirePostFilter) {
                if (!$this->validateFilterForObject((array) $row, $filters)) {
                    continue;
                }
            }
            $result[] = $row['uri'];
        }

        return $result;
    }

    /**
     * Searches through all of a users calendars and calendar objects to find
     * an object with a specific UID.
     *
     * This method should return the path to this object, relative to the
     * calendar home, so this path usually only contains two parts:
     *
     * calendarpath/objectpath.ics
     *
     * If the uid is not found, return null.
     *
     * This method should only consider * objects that the principal owns, so
     * any calendars owned by other principals that also appear in this
     * collection should be ignored.
     *
     * @param string $principalUri
     * @param string $uid
     *
     * @return string|null
     */
    public function getCalendarObjectByUID($principalUri, $uid)
    {
        $collection = $this->db->selectCollection($this->calendarInstancesCollectionName);
        $query = ['principaluri' => $principalUri];
        $projection = [
            'calendarid' => 1,
            'uri' => 1,
            'access' => 1,
        ];

        $calrow = $collection->findOne($query, ['projection' => $projection]);
        if (!$calrow) {
            return null;
        }

        $collection = $this->db->selectCollection($this->calendarObjectCollectionName);
        $query = ['calendarid' => (string) $calrow['calendarid'], 'uid' => $uid];
        $fields = ['uri' => 1];

        $objrow = $collection->findOne($query, ['projection' => $projection]);
        if (!$objrow) {
            return null;
        }

        return $calrow['uri'].'/'.$objrow['uri'];
    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken in the specified calendar.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * ];
     *
     * The returned syncToken property should reflect the *current* syncToken
     * of the calendar, as reported in the {http://sabredav.org/ns}sync-token
     * property this is needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param mixed  $calendarId
     * @param string $syncToken
     * @param int    $syncLevel
     * @param int    $limit
     *
     * @return array
     */
    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null)
    {
        $this->_assertIsArray($calendarId);

        $calendarId = $calendarId[0];

        // Current synctoken
        $collection = $this->db->selectCollection($this->calendarCollectionName);
        $mongoCalendarId = new \MongoDB\BSON\ObjectId($calendarId);
        $projection = ['synctoken' => 1];
        $query = ['_id' => $mongoCalendarId];

        $row = $collection->findOne($query, ['projection' => $projection]);
        if (!$row || is_null($row['synctoken'])) {
            return null;
        }

        $currentToken = $row['synctoken'];

        $result = [
            'syncToken' => $currentToken,
            'added' => [],
            'modified' => [],
            'deleted' => [],
        ];

        if ($syncToken) {
            $projection = [
                'uri' => 1,
                'operation' => 1,
            ];
            $collection = $this->db->selectCollection($this->calendarChangesCollectionName);

            $query = ['synctoken' => ['$gte' => (int) $syncToken, '$lt' => (int) $currentToken],
                      'calendarid' => $mongoCalendarId, ];

            $options = [
                'projection' => $projection,
                'sort' => ['synctoken' => 1],
            ];

            if ($limit > 0) {
                $options['limit'] = $limit;
            }

            $res = $collection->find($query, $options);

            // Fetching all changes
            $changes = [];

            // This loop ensures that any duplicates are overwritten, only the
            // last change on a node is relevant.
            foreach ($res as $row) {
                $changes[$row['uri']] = $row['operation'];
            }

            foreach ($changes as $uri => $operation) {
                switch ($operation) {
                    case 1:
                        $result['added'][] = $uri;
                        break;
                    case 2:
                        $result['modified'][] = $uri;
                        break;
                    case 3:
                        $result['deleted'][] = $uri;
                        break;
                }
            }
        } else {
            // No synctoken supplied, this is the initial sync.
            $collection = $this->db->selectCollection($this->calendarObjectCollectionName);
            $query = ['calendarid' => $calendarId];
            $projection = ['uri' => 1];

            $added = [];
            foreach ($collection->find($query, ['projection' => $projection]) as $row) {
                $added[] = $row['uri'];
            }
            $result['added'] = $added;
        }

        return $result;
    }

    /**
     * @codeCoverageIgnore      Copy/Paste from sabre/dav
     *
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken in the specified calendar.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * ];
     *
     * The returned syncToken property should reflect the *current* syncToken
     * of the calendar, as reported in the {http://sabredav.org/ns}sync-token
     * property this is needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param mixed  $calendarId
     * @param string $syncToken
     * @param int    $syncLevel
     * @param int    $limit
     *
     * @return array
     */
    protected function getDenormalizedData($calendarData)
    {
        $vObject = VObject\Reader::read($calendarData);
        $componentType = null;
        $component = null;
        $firstOccurence = null;
        $lastOccurence = null;
        $uid = null;
        foreach ($vObject->getComponents() as $component) {
            if ('VTIMEZONE' !== $component->name) {
                $componentType = $component->name;
                $uid = (string) $component->UID;
                break;
            }
        }
        if (!$componentType) {
            throw new \Sabre\DAV\Exception\BadRequest('Calendar objects must have a VJOURNAL, VEVENT or VTODO component');
        }
        if ('VEVENT' === $componentType) {
            $firstOccurence = $component->DTSTART->getDateTime()->getTimeStamp();
            // Finding the last occurence is a bit harder
            if (!isset($component->RRULE)) {
                if (isset($component->DTEND)) {
                    $lastOccurence = $component->DTEND->getDateTime()->getTimeStamp();
                } elseif (isset($component->DURATION)) {
                    $endDate = clone $component->DTSTART->getDateTime();
                    $endDate->add(VObject\DateTimeParser::parse($component->DURATION->getValue()));
                    $lastOccurence = $endDate->getTimeStamp();
                } elseif (!$component->DTSTART->hasTime()) {
                    $endDate = clone $component->DTSTART->getDateTime();
                    $endDate->modify('+1 day');
                    $lastOccurence = $endDate->getTimeStamp();
                } else {
                    $lastOccurence = $firstOccurence;
                }
            } else {
                $it = new VObject\Recur\EventIterator($vObject, (string) $component->UID);
                $maxDate = new \DateTime(self::MAX_DATE);
                if ($it->isInfinite()) {
                    $lastOccurence = $maxDate->getTimeStamp();
                } else {
                    $end = $it->getDtEnd();
                    while ($it->valid() && $end < $maxDate) {
                        $end = $it->getDtEnd();
                        $it->next();
                    }
                    $lastOccurence = $end->getTimeStamp();
                }
            }
        }

        return [
            'etag' => md5($calendarData),
            'size' => strlen($calendarData),
            'componentType' => $componentType,
            'firstOccurence' => $firstOccurence,
            'lastOccurence' => $lastOccurence,
            'uid' => $uid,
        ];
    }

    /**
     * Adds a change record to the calendarchanges collection.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     * @param int    $operation  1 = add, 2 = modify, 3 = delete
     */
    protected function addChange($calendarId, $objectUri, $operation)
    {
        $calcollection = $this->db->selectCollection($this->calendarCollectionName);
        $mongoCalendarId = new \MongoDB\BSON\ObjectId($calendarId);
        $query = ['_id' => $mongoCalendarId];
        $res = $calcollection->findOne($query, ['projection' => ['synctoken' => 1]]);

        $changecollection = $this->db->selectCollection($this->calendarChangesCollectionName);
        $obj = [
            'uri' => $objectUri,
            'synctoken' => $res['synctoken'],
            'calendarid' => $mongoCalendarId,
            'operation' => $operation,
        ];
        $changecollection->insertOne($obj);

        $update = ['$inc' => ['synctoken' => 1]];
        $calcollection->updateOne($query, $update);
    }

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
     * 9. {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     *    (should just be an instance of
     *    Sabre\CalDAV\Property\SupportedCalendarComponentSet, with a bunch of
     *    default components).
     *
     * @param string $principalUri
     *
     * @return array
     */
    public function getSubscriptionsForUser($principalUri)
    {
        $fields = array_values($this->subscriptionPropertyMap);
        $fields[] = '_id';
        $fields[] = 'uri';
        $fields[] = 'source';
        $fields[] = 'principaluri';
        $fields[] = 'lastmodified';

        // Making fields a comma-delimited list
        $collection = $this->db->selectCollection($this->calendarSubscriptionsCollectionName);
        $query = ['principaluri' => $principalUri];
        $projection = array_fill_keys($fields, 1);
        $options = [
            'projection' => $projection,
            'sort' => ['calendarorder' => 1],
        ];

        $res = $collection->find($query, $options);

        $subscriptions = [];
        foreach ($res as $row) {
            $subscription = [
                'id' => (string) $row['_id'],
                'uri' => $row['uri'],
                'principaluri' => $row['principaluri'],
                'source' => $row['source'],
                'lastmodified' => $row['lastmodified'],
                '{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet(['VTODO', 'VEVENT']),
            ];

            foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
                if (!is_null($row[$dbName])) {
                    $subscription[$xmlName] = $row[$dbName];
                }
            }

            $subscriptions[] = $subscription;
        }

        return $subscriptions;
    }

    /**
     * Creates a new subscription for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this subscription in other methods, such as updateSubscription.
     *
     * @param string $principalUri
     * @param string $uri
     * @param array  $properties
     *
     * @return mixed
     */
    public function createSubscription($principalUri, $uri, array $properties)
    {
        if (!isset($properties['{http://calendarserver.org/ns/}source'])) {
            throw new \Sabre\DAV\Exception\Forbidden('The {http://calendarserver.org/ns/}source property is required when creating subscriptions');
        }

        $obj = [
            'principaluri' => $principalUri,
            'uri' => $uri,
            'source' => $properties['{http://calendarserver.org/ns/}source']->getHref(),
            'lastmodified' => time(),
        ];

        foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {
                $obj[$dbName] = $properties[$xmlName];
            } else {
                $obj[$dbName] = null;
            }
        }

        $collection = $this->db->selectCollection($this->calendarSubscriptionsCollectionName);
        $insertResult = $collection->insertOne($obj);

        return (string) $insertResult->getInsertedId();
    }

    /**
     * Updates a subscription.
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
     * @param mixed                $subscriptionId
     * @param \Sabre\DAV\PropPatch $propPatch
     */
    public function updateSubscription($subscriptionId, \Sabre\DAV\PropPatch $propPatch)
    {
        $supportedProperties = array_keys($this->subscriptionPropertyMap);
        $supportedProperties[] = '{http://calendarserver.org/ns/}source';

        $propPatch->handle($supportedProperties, function ($mutations) use ($subscriptionId) {
            $newValues = [];
            $newValues['lastmodified'] = time();

            foreach ($mutations as $propertyName => $propertyValue) {
                if ('{http://calendarserver.org/ns/}source' === $propertyName) {
                    $newValues['source'] = $propertyValue->getHref();
                } else {
                    $fieldName = $this->subscriptionPropertyMap[$propertyName];
                    $newValues[$fieldName] = $propertyValue;
                }
            }

            $collection = $this->db->selectCollection($this->calendarSubscriptionsCollectionName);
            $query = ['_id' => new \MongoDB\BSON\ObjectId($subscriptionId)];
            $collection->updateMany($query, ['$set' => $newValues]);

            return true;
        });
    }

    /**
     * Deletes a subscription.
     *
     * @param mixed $subscriptionId
     */
    public function deleteSubscription($subscriptionId)
    {
        $collection = $this->db->selectCollection($this->calendarSubscriptionsCollectionName);
        $collection->deleteMany(['_id' => new \MongoDB\BSON\ObjectId($subscriptionId)]);
    }

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
     *
     * @return array
     */
    public function getSchedulingObject($principalUri, $objectUri)
    {
        $collection = $this->db->selectCollection($this->schedulingObjectCollectionName);
        $query = ['principaluri' => $principalUri, 'uri' => $objectUri];
        $projection = [
            'uri' => 1,
            'calendardata' => 1,
            'lastmodified' => 1,
            'etag' => 1,
            'size' => 1,
        ];
        $row = $collection->findOne($query, ['projection' => $projection]);
        if (!$row) {
            return null;
        }

        return [
            'uri' => $row['uri'],
            'calendardata' => $row['calendardata'],
            'lastmodified' => $row['lastmodified'],
            'etag' => '"'.$row['etag'].'"',
            'size' => (int) $row['size'],
        ];
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
     *
     * @return array
     */
    public function getSchedulingObjects($principalUri)
    {
        $collection = $this->db->selectCollection($this->schedulingObjectCollectionName);
        $query = ['principaluri' => $principalUri];
        $projection = [
            'uri' => 1,
            'calendardata' => 1,
            'lastmodified' => 1,
            'etag' => 1,
            'size' => 1,
        ];
        $result = [];
        foreach ($collection->find($query, ['projection' => $projection]) as $row) {
            $result[] = [
                'calendardata' => $row['calendardata'],
                'uri' => $row['uri'],
                'lastmodified' => $row['lastmodified'],
                'etag' => '"'.$row['etag'].'"',
                'size' => (int) $row['size'],
            ];
        }

        return $result;
    }

    /**
     * Deletes a scheduling object.
     *
     * @param string $principalUri
     * @param string $objectUri
     */
    public function deleteSchedulingObject($principalUri, $objectUri)
    {
        $collection = $this->db->selectCollection($this->schedulingObjectCollectionName);
        $query = ['principaluri' => $principalUri, 'uri' => $objectUri];
        $collection->deleteMany($query);
    }

    /**
     * Creates a new scheduling object. This should land in a users' inbox.
     *
     * @param string $principalUri
     * @param string $objectUri
     * @param string $objectData
     */
    public function createSchedulingObject($principalUri, $objectUri, $objectData)
    {
        $collection = $this->db->selectCollection($this->schedulingObjectCollectionName);
        $obj = [
            'principaluri' => $principalUri,
            'calendardata' => $objectData,
            'uri' => $objectUri,
            'lastmodified' => time(),
            'etag' => md5($objectData),
            'size' => strlen($objectData),
        ];
        $collection->insertOne($obj);
    }

    /**
     * Updates the list of shares.
     *
     * @param mixed                           $calendarId
     * @param \Sabre\DAV\Xml\Element\Sharee[] $sharees
     */
    public function updateInvites($calendarId, array $sharees)
    {
        $this->_assertIsArray($calendarId);

        $currentInvites = $this->getInvites($calendarId);
        list($calendarId, $instanceId) = $calendarId;
        $mongoCalendarId = new \MongoDB\BSON\ObjectId($calendarId);
        $mongoInstanceId = new \MongoDB\BSON\ObjectId($instanceId);

        $collection = $this->db->selectCollection($this->calendarInstancesCollectionName);
        $existingInstance = $collection->findOne(['_id' => $mongoInstanceId], ['projection' => ['_id' => 0]]);

        foreach ($sharees as $sharee) {
            if (\Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS === $sharee->access) {
                // TODO access === ACCESS_READ || access === ACCESS_READWRITE
                $collection->deleteMany(['calendarid' => $mongoCalendarId, 'share_href' => $sharee->href]);
                continue;
            }

            if (is_null($sharee->principal)) {
                $sharee->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_INVALID;
            } else {
                $sharee->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED;
            }

            foreach ($currentInvites as $oldSharee) {
                if ($oldSharee->href === $sharee->href) {
                    $sharee->properties = array_merge($oldSharee->properties, $sharee->properties);
                    $collection->updateMany(['calendarid' => $mongoCalendarId, 'share_href' => $sharee->href], ['$set' => [
                        'access' => $sharee->access,
                        'share_displayname' => isset($sharee->properties['{DAV:}displayname']) ? $sharee->properties['{DAV:}displayname'] : null,
                        'share_invitestatus' => $sharee->inviteStatus ?: $oldSharee->inviteStatus,
                    ]]);
                    continue 2;
                }
            }

            $existingInstance['calendarid'] = $mongoCalendarId;
            $existingInstance['principaluri'] = $sharee->principal;
            $existingInstance['access'] = $sharee->access;
            $existingInstance['uri'] = \Sabre\DAV\UUIDUtil::getUUID();
            $existingInstance['share_href'] = $sharee->href;
            $existingInstance['share_displayname'] = isset($sharee->properties['{DAV:}displayname']) ? $sharee->properties['{DAV:}displayname'] : null;
            $existingInstance['share_invitestatus'] = $sharee->inviteStatus ?: \Sabre\DAV\Sharing\Plugin::INVITE_NORESPONSE;
            $collection->insertOne($existingInstance);
        }
    }

    /**
     * Returns the list of people whom a calendar is shared with.
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
     *
     * @return \Sabre\DAV\Xml\Element\Sharee[]
     */
    public function getInvites($calendarId)
    {
        $this->_assertIsArray($calendarId);

        $calendarId = $calendarId[0];
        $mongoCalendarId = new \MongoDB\BSON\ObjectId($calendarId);

        $projection = [
            'principaluri' => 1,
            'access' => 1,
            'share_href' => 1,
            'share_invitestatus' => 1,
            'share_displayname' => 1,
        ];
        $collection = $this->db->selectCollection($this->calendarInstancesCollectionName);
        $query = ['calendarid' => $mongoCalendarId];
        $res = $collection->find($query, ['projection' => $projection]);

        $result = [];
        foreach ($res as $row) {
            $result[] = new \Sabre\DAV\Xml\Element\Sharee([
                'href' => isset($row['share_href']) ? $row['share_href'] : \Sabre\HTTP\encodePath($row['principaluri']),
                'access' => (int) $row['access'],
                'inviteStatus' => (int) $row['share_invitestatus'],
                'properties' => !empty($row['share_displayname']) ? ['{DAV:}displayname' => $row['share_displayname']] : [],
                'principal' => $row['principaluri'],
            ]);
        }

        return $result;
    }

    /**
     * Publishes a calendar.
     *
     * @param mixed $calendarId
     * @param bool  $value
     */
    public function setPublishStatus($calendarId, $value)
    {
        throw new \Exception('Not implemented');
    }

    protected function _assertIsArray($calendarId)
    {
        if (!is_array($calendarId)) {
            throw new \LogicException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
    }
}
