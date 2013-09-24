<?php

namespace Sabre\CalDAV;

use
    Sabre\DAV,
    Sabre\VObject,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface,
    Sabre\DAV\Exception\BadRequest,
    DateTime;

/**
 * ICS Exporter
 *
 * This plugin adds the ability to export entire calendars as .ics files.
 * This is useful for clients that don't support CalDAV yet. They often do
 * support ics files.
 *
 * To use this, point a http client to a caldav calendar, and add ?expand to
 * the url.
 *
 * Further options that can be added to the url:
 *   start=123456789 - Only return events after the given unix timestamp
 *   end=123245679   - Only return events from before the given unix timestamp
 *   expand=1        - Strip timezone information and expand recurring events.
 *                     If you'd like to expand, you _must_ also specify start
 *                     and end.
 *
 * By default this plugin returns data in the text/calendar format (iCalendar
 * 2.0). If you'd like to receive jCal data instead, you can use an Accept
 * header:
 *
 * Accept: application/calendar+json
 *
 * Alternatively, you can also specify this in the url using
 * accept=application/calendar+json, or accept=jcal for short. If the url
 * parameter and Accept header is specified, the url parameter wins.
 *
 * Note that specifying a start or end data implies that only events will be
 * returned. VTODO and VJOURNAL will be stripped.
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class ICSExportPlugin extends DAV\ServerPlugin {

    /**
     * Reference to Server class
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;

    /**
     * Initializes the plugin and registers event handlers
     *
     * @param \Sabre\DAV\Server $server
     * @return void
     */
    public function initialize(DAV\Server $server) {

        $this->server = $server;
        $this->server->on('method:GET', [$this,'httpGet'], 90);

    }

    /**
     * Intercepts GET requests on calendar urls ending with ?export.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response) {

        $queryParams = $request->getQueryParameters();
        if (!array_key_exists('export', $queryParams)) return;

        $path = $request->getPath();

        $node = $this->server->getProperties($path, [
            '{DAV:}resourcetype',
            '{DAV:}displayname',
        ]);

        if (!isset($node['{DAV:}resourcetype']) || !$node['{DAV:}resourcetype']->is('{' . Plugin::NS_CALDAV . '}calendar')) {
            return;
        }
        // Marking the transactionType, for logging purposes.
        $this->server->transactionType = 'get-calendar-export';

        $displayName = isset($node['{DAV:}displayname'])?$node['{DAV:}displayname']:dirname($path);

        $start = null;
        $end = null;
        $expand = false;
        if (isset($queryParams['start'])) {
            if (!ctype_digit($queryParams['start'])) {
                throw new BadRequest('The start= parameter must contain a unix timestamp');
            }
            $start = DateTime::createFromFormat('U', $queryParams['start']);
        }
        if (isset($queryParams['end'])) {
            if (!ctype_digit($queryParams['end'])) {
                throw new BadRequest('The end= parameter must contain a unix timestamp');
            }
            $end = DateTime::createFromFormat('U', $queryParams['end']);
        }
        if (isset($queryParams['expand']) && !!$queryParams['expand']) {
            if (!$start || !$end) {
                throw new BadRequest('If you\'d like to expand recurrences, you must specify both a start= and end= parameter.');
            }
            $expand = true;
        }

        $format = \Sabre\HTTP\Util::Negotiate(
            $request->getHeader('Accept'),
            [
                'text/calendar',
                'application/calendar+json',
            ]
        );

        if (isset($queryParams['accept'])) {
            if ($queryParams['accept'] === 'application/calendar+json' || $queryParams['accept'] === 'jcal') {
                $format = 'application/calendar+json';
            }
        }
        if (!$format) {
            $format = 'text/calendar';
        }

        $calDataProp = '{' . Plugin::NS_CALDAV . '}calendar-data';

        $blobs = [];
        if ($start || $end) {

            // If there was a start or end filter, we need to enlist
            // calendarQuery for speed.
            $calendarNode = $this->server->tree->getNodeForPath($path);
            $queryResult = $calendarNode->calendarQuery([
                'name' => 'VCALENDAR',
                'comp-filters' => [
                    [
                        'name' => 'VEVENT',
                        'comp-filters' => [],
                        'prop-filters' => [],
                        'is-not-defined' => false,
                        'time-range' => [
                            'start' => $start,
                            'end' => $end,
                        ],
                    ],
                ],
                'prop-filters' => [],
                'is-not-defined' => false,
                'time-range' => null,
            ]);

            // queryResult is just a list of base urls. We need to prefix the
            // calendar path.
            $queryResult = array_map(function($item) use ($path) { return $path . '/' . $item; }, $queryResult);
            $nodes = $this->server->getPropertiesForMultiplePaths($queryResult, [$calDataProp]);
            unset($queryResult);

        } else {
            $nodes = $this->server->getPropertiesForPath($path, [$calDataProp], 1);
        }

        // Flattening the arrays
        foreach($nodes as $node) {
            if (isset($node[200][$calDataProp])) {
                $blobs[] = $node[200][$calDataProp];
            }
        }
        unset($nodes);

        $mergedCalendar = $this->mergeObjects(
            $displayName,
            $blobs
        );

        if ($expand) {
            $mergedCalendar->expand($start, $end);
        }

        $response->setHeader('Content-Type', $format);

        switch($format) {
            case 'text/calendar' :
                $mergedCalendar = $mergedCalendar->serialize();
                break;
            case 'application/calendar+json' :
                $mergedCalendar = json_encode($mergedCalendar->jsonSerialize());
                break;
        }

        $response->setStatus(200);
        $response->setBody($mergedCalendar);

        // Returning false to break the event chain
        return false;

    }

    /**
     * Merges all calendar objects, and builds one big iCalendar blob.
     *
     * @param string $displayName
     * @param array $inputObjects
     * @return VObject\Component\VCalendar
     */
    public function mergeObjects($displayName, array $inputObjects) {

        $calendar = new VObject\Component\VCalendar();
        $calendar->version = '2.0';
        if (DAV\Server::$exposeVersion) {
            $calendar->prodid = '-//SabreDAV//SabreDAV ' . DAV\Version::VERSION . '//EN';
        } else {
            $calendar->prodid = '-//SabreDAV//SabreDAV//EN';
        }
        $calendar->{'X-WR-CALNAME'} = $displayName;

        $collectedTimezones = [];

        $timezones = [];
        $objects = [];

        foreach($inputObjects as $inputObject) {

            $nodeComp = VObject\Reader::read($inputObject);

            foreach($nodeComp->children() as $child) {

                switch($child->name) {
                    case 'VEVENT' :
                    case 'VTODO' :
                    case 'VJOURNAL' :
                        $objects[] = $child;
                        break;

                    // VTIMEZONE is special, because we need to filter out the duplicates
                    case 'VTIMEZONE' :
                        // Naively just checking tzid.
                        if (in_array((string)$child->TZID, $collectedTimezones)) continue;

                        $timezones[] = $child;
                        $collectedTimezones[] = $child->TZID;
                        break;

                }

            }

        }

        foreach($timezones as $tz) $calendar->add($tz);
        foreach($objects as $obj) $calendar->add($obj);

        return $calendar;

    }

}
