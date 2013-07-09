<?php

namespace Sabre\CalDAV;

use
    Sabre\DAV,
    Sabre\VObject,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface;

/**
 * ICS Exporter
 *
 * This plugin adds the ability to export entire calendars as .ics files.
 * This is useful for clients that don't support CalDAV yet. They often do
 * support ics files.
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

        // splitting uri
        list($path) = explode('?',$path,2);

        $node = $this->server->tree->getNodeForPath($path);

        if (!($node instanceof Calendar)) return;

        // Checking ACL, if available.
        if ($aclPlugin = $this->server->getPlugin('acl')) {
            $aclPlugin->checkPrivileges($path, '{DAV:}read');
        }

        $this->server->transactionType = 'get-calendar-export';
        $response->setHeader('Content-Type','text/calendar');
        $response->setStatus(200);

        $nodes = $this->server->getNodesForPath($path,1);

        $response->setBody($this->generateICS($nodes));

        // Returning false to break the event chain
        return false;

    }

    /**
     * Merges all calendar objects, and builds one big ics export
     *
     * @param array $nodes
     * @return string
     */
    public function generateICS(array $nodes) {

        $calendar = new VObject\Component\VCalendar();
        $calendar->version = '2.0';
        if (DAV\Server::$exposeVersion) {
            $calendar->prodid = '-//SabreDAV//SabreDAV ' . DAV\Version::VERSION . '//EN';
        } else {
            $calendar->prodid = '-//SabreDAV//SabreDAV//EN';
        }
        $calendar->calscale = 'GREGORIAN';

        $collectedTimezones = array();

        $timezones = array();
        $objects = array();

        foreach($nodes as $path => $node) {
            if(($node = $this->server->getPathProperties($path, [
                '{' . Plugin::NS_CALDAV . '}calendar-data',
            ], $node)) === false) {
                continue;
            }

            if (!isset($node[200]['{' . Plugin::NS_CALDAV . '}calendar-data'])) {
                continue;
            }
            $nodeData = $node[200]['{' . Plugin::NS_CALDAV . '}calendar-data'];

            $nodeComp = VObject\Reader::read($nodeData);

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

        return $calendar->serialize();

    }

}
