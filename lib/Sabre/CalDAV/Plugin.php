<?php

/**
 * CalDAV plugin
 *
 * This plugin provides functionality added by CalDAV (RFC 4791)
 * It implements new reports, and the MKCALENDAR method.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * This is the official CalDAV namespace
     */
    const NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';

    /**
     * This is the namespace for the proprietary calendarserver extensions
     */
    const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

    /**
     * The following constants are used to differentiate
     * the various filters for the calendar-query report
     */
    const FILTER_COMPFILTER   = 1;
    const FILTER_TIMERANGE    = 3;
    const FILTER_PROPFILTER   = 4;
    const FILTER_PARAMFILTER  = 5;
    const FILTER_TEXTMATCH    = 6;

    /**
     * The hardcoded root for calendar objects. It is unfortunate
     * that we're stuck with it, but it will have to do for now
     */
    const CALENDAR_ROOT = 'calendars';

    /**
     * Reference to server object
     *
     * @var Sabre_DAV_Server
     */
    private $server;

    /**
     * Use this method to tell the server this plugin defines additional
     * HTTP methods.
     *
     * This method is passed a uri. It should only return HTTP methods that are
     * available for the specified uri.
     *
     * @param string $uri
     * @return array
     */
    public function getHTTPMethods($uri) {

        // The MKCALENDAR is only available on unmapped uri's, whose
        // parents extend IExtendedCollection
        list($parent, $name) = Sabre_DAV_URLUtil::splitPath($uri);

        $node = $this->server->tree->getNodeForPath($parent);

        if ($node instanceof Sabre_DAV_IExtendedCollection) {
            try {
                $node->getChild($name);
            } catch (Sabre_DAV_Exception_NotFound $e) {
                return array('MKCALENDAR');
            }
        }
        return array();

    }

    /**
     * Returns a list of features for the DAV: HTTP header.
     *
     * @return array
     */
    public function getFeatures() {

        return array('calendar-access', 'calendar-proxy');

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using Sabre_DAV_Server::getPlugin
     *
     * @return string
     */
    public function getPluginName() {

        return 'caldav';

    }

    /**
     * Returns a list of reports this plugin supports.
     *
     * This will be used in the {DAV:}supported-report-set property.
     * Note that you still need to subscribe to the 'report' event to actually
     * implement them
     *
     * @param string $uri
     * @return array
     */
    public function getSupportedReportSet($uri) {

        $node = $this->server->tree->getNodeForPath($uri);

        $reports = array();
        if ($node instanceof Sabre_CalDAV_ICalendar || $node instanceof Sabre_CalDAV_ICalendarObject) {
            $reports[] = '{' . self::NS_CALDAV . '}calendar-multiget';
            $reports[] = '{' . self::NS_CALDAV . '}calendar-query';
        }
        if ($node instanceof Sabre_CalDAV_ICalendar) {
            $reports[] = '{' . self::NS_CALDAV . '}free-busy-query';
        }
        return $reports;

    }

    /**
     * Initializes the plugin
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('unknownMethod',array($this,'unknownMethod'));
        //$server->subscribeEvent('unknownMethod',array($this,'unknownMethod2'),1000);
        $server->subscribeEvent('report',array($this,'report'));
        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));
        $server->subscribeEvent('onHTMLActionsPanel', array($this,'htmlActionsPanel'));
        $server->subscribeEvent('onBrowserPostAction', array($this,'browserPostAction'));

        $server->xmlNamespaces[self::NS_CALDAV] = 'cal';
        $server->xmlNamespaces[self::NS_CALENDARSERVER] = 'cs';

        $server->propertyMap['{' . self::NS_CALDAV . '}supported-calendar-component-set'] = 'Sabre_CalDAV_Property_SupportedCalendarComponentSet';

        $server->resourceTypeMapping['Sabre_CalDAV_ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';
        $server->resourceTypeMapping['Sabre_CalDAV_Principal_ProxyRead'] = '{http://calendarserver.org/ns/}calendar-proxy-read';
        $server->resourceTypeMapping['Sabre_CalDAV_Principal_ProxyWrite'] = '{http://calendarserver.org/ns/}calendar-proxy-write';

        array_push($server->protectedProperties,

            '{' . self::NS_CALDAV . '}supported-calendar-component-set',
            '{' . self::NS_CALDAV . '}supported-calendar-data',
            '{' . self::NS_CALDAV . '}max-resource-size',
            '{' . self::NS_CALDAV . '}min-date-time',
            '{' . self::NS_CALDAV . '}max-date-time',
            '{' . self::NS_CALDAV . '}max-instances',
            '{' . self::NS_CALDAV . '}max-attendees-per-instance',
            '{' . self::NS_CALDAV . '}calendar-home-set',
            '{' . self::NS_CALDAV . '}supported-collation-set',
            '{' . self::NS_CALDAV . '}calendar-data',

            // scheduling extension
            '{' . self::NS_CALDAV . '}calendar-user-address-set',

            // CalendarServer extensions
            '{' . self::NS_CALENDARSERVER . '}getctag',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-read-for',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for'

        );
    }

    /**
     * This function handles support for the MKCALENDAR method
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function unknownMethod($method, $uri) {

        if ($method!=='MKCALENDAR') return;

        $this->httpMkCalendar($uri);
        // false is returned to stop the unknownMethod event
        return false;

    }

    /**
     * This functions handles REPORT requests specific to CalDAV
     *
     * @param string $reportName
     * @param DOMNode $dom
     * @return bool
     */
    public function report($reportName,$dom) {

        switch($reportName) {
            case '{'.self::NS_CALDAV.'}calendar-multiget' :
                $this->calendarMultiGetReport($dom);
                return false;
            case '{'.self::NS_CALDAV.'}calendar-query' :
                $this->calendarQueryReport($dom);
                return false;
            case '{'.self::NS_CALDAV.'}free-busy-query' :
                $this->freeBusyQueryReport($dom);
                return false;

        }


    }

    /**
     * This function handles the MKCALENDAR HTTP method, which creates
     * a new calendar.
     *
     * @param string $uri
     * @return void
     */
    public function httpMkCalendar($uri) {

        // Due to unforgivable bugs in iCal, we're completely disabling MKCALENDAR support
        // for clients matching iCal in the user agent
        //$ua = $this->server->httpRequest->getHeader('User-Agent');
        //if (strpos($ua,'iCal/')!==false) {
        //    throw new Sabre_DAV_Exception_Forbidden('iCal has major bugs in it\'s RFC3744 support. Therefore we are left with no other choice but disabling this feature.');
        //}

        $body = $this->server->httpRequest->getBody(true);
        $properties = array();

        if ($body) {

            $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body);

            foreach($dom->firstChild->childNodes as $child) {

                if (Sabre_DAV_XMLUtil::toClarkNotation($child)!=='{DAV:}set') continue;
                foreach(Sabre_DAV_XMLUtil::parseProperties($child,$this->server->propertyMap) as $k=>$prop) {
                    $properties[$k] = $prop;
                }

            }
        }

        $resourceType = array('{DAV:}collection','{urn:ietf:params:xml:ns:caldav}calendar');

        $this->server->createCollection($uri,$resourceType,$properties);

        $this->server->httpResponse->sendStatus(201);
        $this->server->httpResponse->setHeader('Content-Length',0);
    }

    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific
     * properties.
     *
     * @param string $path
     * @param Sabre_DAV_INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, Sabre_DAV_INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof Sabre_DAVACL_IPrincipal) {

            // calendar-home-set property
            $calHome = '{' . self::NS_CALDAV . '}calendar-home-set';
            if (in_array($calHome,$requestedProperties)) {
                $principalId = $node->getName();
                $calendarHomePath = self::CALENDAR_ROOT . '/' . $principalId . '/';
                unset($requestedProperties[$calHome]);
                $returnedProperties[200][$calHome] = new Sabre_DAV_Property_Href($calendarHomePath);
            }

            // calendar-user-address-set property
            $calProp = '{' . self::NS_CALDAV . '}calendar-user-address-set';
            if (in_array($calProp,$requestedProperties)) {

                $addresses = $node->getAlternateUriSet();
                $addresses[] = $this->server->getBaseUri() . $node->getPrincipalUrl();
                unset($requestedProperties[$calProp]);
                $returnedProperties[200][$calProp] = new Sabre_DAV_Property_HrefList($addresses, false);

            }

            // These two properties are shortcuts for ical to easily find
            // other principals this principal has access to.
            $propRead = '{' . self::NS_CALENDARSERVER . '}calendar-proxy-read-for';
            $propWrite = '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for';
            if (in_array($propRead,$requestedProperties) || in_array($propWrite,$requestedProperties)) {

                $membership = $node->getGroupMembership();
                $readList = array();
                $writeList = array();

                foreach($membership as $group) {

                    $groupNode = $this->server->tree->getNodeForPath($group);

                    // If the node is either ap proxy-read or proxy-write
                    // group, we grab the parent principal and add it to the
                    // list.
                    if ($groupNode instanceof Sabre_CalDAV_Principal_ProxyRead) {
                        list($readList[]) = Sabre_DAV_URLUtil::splitPath($group);
                    }
                    if ($groupNode instanceof Sabre_CalDAV_Principal_ProxyWrite) {
                        list($writeList[]) = Sabre_DAV_URLUtil::splitPath($group);
                    }

                }
                if (in_array($propRead,$requestedProperties)) {
                    unset($requestedProperties[$propRead]);
                    $returnedProperties[200][$propRead] = new Sabre_DAV_Property_HrefList($readList);
                }
                if (in_array($propWrite,$requestedProperties)) {
                    unset($requestedProperties[$propWrite]);
                    $returnedProperties[200][$propWrite] = new Sabre_DAV_Property_HrefList($writeList);
                }

            }

        } // instanceof IPrincipal


        if ($node instanceof Sabre_CalDAV_ICalendarObject) {
            // The calendar-data property is not supposed to be a 'real'
            // property, but in large chunks of the spec it does act as such.
            // Therefore we simply expose it as a property.
            $calDataProp = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}calendar-data';
            if (in_array($calDataProp, $requestedProperties)) {
                unset($requestedProperties[$calDataProp]);
                $val = $node->get();
                if (is_resource($val))
                    $val = stream_get_contents($val);

                // Taking out \r to not screw up the xml output
                $returnedProperties[200][$calDataProp] = str_replace("\r","", $val);

            }
        }

    }

    /**
     * This function handles the calendar-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     *
     * @param DOMNode $dom
     * @return void
     */
    public function calendarMultiGetReport($dom) {

        $properties = array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild));

        $hrefElems = $dom->getElementsByTagNameNS('urn:DAV','href');
        foreach($hrefElems as $elem) {
            $uri = $this->server->calculateUri($elem->nodeValue);
            list($objProps) = $this->server->getPropertiesForPath($uri,$properties);
            $propertyList[]=$objProps;

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($propertyList));

    }

    /**
     * This function handles the calendar-query REPORT
     *
     * This report is used by clients to request calendar objects based on
     * complex conditions.
     *
     * @param DOMNode $dom
     * @return void
     */
    public function calendarQueryReport($dom) {

        $parser = new Sabre_CalDAV_CalendarQueryParser($dom);
        $parser->parse();

        $requestedCalendarData = true;
        $requestedProperties = $parser->requestedProperties;

        if (!in_array('{urn:ietf:params:xml:ns:caldav}calendar-data', $requestedProperties)) {

            // We always retrieve calendar-data, as we need it for filtering.
            $requestedProperties[] = '{urn:ietf:params:xml:ns:caldav}calendar-data';

            // If calendar-data wasn't explicitly requested, we need to remove
            // it after processing.
            $requestedCalendarData = false;
        }

        // These are the list of nodes that potentially match the requirement
        $candidateNodes = $this->server->getPropertiesForPath(
            $this->server->getRequestUri(),
            $requestedProperties,
            $this->server->getHTTPDepth(0)
        );

        $verifiedNodes = array();

        $validator = new Sabre_CalDAV_CalendarQueryValidator();

        foreach($candidateNodes as $node) {

            // If the node didn't have a calendar-data property, it must not be a calendar object
            if (!isset($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']))
                continue;

            if ($validator->validate($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'],$parser->filters)) {

                if (!$requestedCalendarData) {
                    unset($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                }
                $verifiedNodes[] = $node;
            }

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($verifiedNodes));

    }

    /**
     * This method is responsible for parsing the request and generating the
     * response for the CALDAV:free-busy-query REPORT.
     *
     * @param DOMNode $dom
     * @return void
     */
    protected function freeBusyQueryReport(DOMNode $dom) {

        $start = null;
        $end = null;

        foreach($dom->firstChild->childNodes as $childNode) {

            $clark = Sabre_DAV_XMLUtil::toClarkNotation($childNode);
            if ($clark == '{' . self::NS_CALDAV . '}time-range') {
                $start = $childNode->getAttribute('start');
                $end = $childNode->getAttribute('end');
                break;
            }

        }
        if ($start) {
            $start = Sabre_VObject_DateTimeParser::parseDateTime($start);
        }
        if ($end) {
            $end = Sabre_VObject_DateTimeParser::parseDateTime($end);
        }

        if (!$start && !$end) {
            throw new Sabre_DAV_Exception_BadRequest('The freebusy report must have a time-range filter');
        }
        $acl = $this->server->getPlugin('acl');

        if (!$acl) {
            throw new Sabre_DAV_Exception('The ACL plugin must be loaded for free-busy queries to work');
        }
        $uri = $this->server->getRequestUri();
        $acl->checkPrivileges($uri,'{' . self::NS_CALDAV . '}read-free-busy');

        $calendar = $this->server->tree->getNodeForPath($uri);
        if (!$calendar instanceof Sabre_CalDAV_ICalendar) {
            throw new Sabre_DAV_Exception_NotImplemented('The free-busy-query REPORT is only implemented on calendars');
        }

        $objects = array_map(function($child) {
            $obj = $child->get();
            if (is_resource($obj)) {
                $obj = stream_get_contents($obj);
            }
            return $obj;
        }, $calendar->getChildren());

        $generator = new Sabre_VObject_FreeBusyGenerator();
        $generator->setObjects($objects);
        $generator->setTimeRange($start, $end);
        $result = $generator->getResult();
        $result = $result->serialize();

        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->setHeader('Content-Type', 'text/calendar');
        $this->server->httpResponse->setHeader('Content-Length', strlen($result));
        $this->server->httpResponse->sendBody($result);

    }


    /**
     * This method is used to generate HTML output for the
     * Sabre_DAV_Browser_Plugin. This allows us to generate an interface users
     * can use to create new calendars.
     *
     * @param Sabre_DAV_INode $node
     * @param string $output
     * @return bool
     */
    public function htmlActionsPanel(Sabre_DAV_INode $node, &$output) {

        if (!$node instanceof Sabre_CalDAV_UserCalendars)
            return;

        $output.= '<tr><td><form method="post" action="">
            <h3>Create new calendar</h3>
            <input type="hidden" name="sabreAction" value="mkcalendar" />
            <label>Name (uri):</label> <input type="text" name="name" /><br />
            <label>Display name:</label> <input type="text" name="{DAV:}displayname" /><br />
            <input type="submit" value="create" />
            </form>
            </td></tr>';

        return false;

    }

    /**
     * This method allows us to intercept the 'mkcalendar' sabreAction. This
     * action enables the user to create new calendars from the browser plugin.
     *
     * @param string $uri
     * @param string $action
     * @param array $postVars
     * @return bool
     */
    public function browserPostAction($uri, $action, array $postVars) {

        if ($action!=='mkcalendar')
            return;

        $resourceType = array('{DAV:}collection','{urn:ietf:params:xml:ns:caldav}calendar');
        $properties = array();
        if (isset($postVars['{DAV:}displayname'])) {
            $properties['{DAV:}displayname'] = $postVars['{DAV:}displayname'];
        }
        $this->server->createCollection($uri . '/' . $postVars['name'],$resourceType,$properties);
        return false;

    }

}
