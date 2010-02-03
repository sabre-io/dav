<?php

/**
 * CalDAV plugin
 *
 * This plugin provides functionality added by CalDAV (RFC 4791)
 * It implements new reports, and the MKCALENDAR method.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Plugin extends Sabre_DAV_ServerPlugin {

    const NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';

    const FILTER_COMPFILTER   = 1;
    const FILTER_ISNOTDEFINED = 2;
    const FILTER_TIMERANGE    = 3;
    const FILTER_PROPFILTER   = 4;
    const FILTER_PARAMFILTER  = 5;
    const FILTER_TEXTMATCH    = 6;

    const CALENDAR_ROOT = 'calendars';

    /**
     * Reference to server object 
     * 
     * @var Sabre_DAV_Server 
     */
    private $server;

    /**
     * Returns a list of supported HTTP methods. 
     * 
     * @return array 
     */
    public function getHTTPMethods() {

        return array('MKCALENDAR');

    }

    /**
     * Returns a list of features for the DAV: HTTP header. 
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('calendar-access');

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
        $server->subscribeEvent('report',array($this,'report'));
        $server->subscribeEvent('afterGetProperties',array($this,'afterGetProperties'));

        $server->xmlNamespaces[self::NS_CALDAV] = 'cal';

    }

    /**
     * This function handles support for the MKCALENDAR and ACL methods
     * 
     * @param string $method 
     * @return bool 
     */
    public function unknownMethod($method) {

        if ($method!=='MKCALENDAR') return;

        $this->httpMkCalendar();
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
            default :
                return true;

        }


    }

    /**
     * This function handles the MKCALENDAR HTTP method, which creates
     * a new calendar.
     * 
     * @return void 
     */
    public function httpMkCalendar() {

        // Due to unforgivable bugs in iCal, we're completely disabling MKCALENDAR support
        // for clients matching iCal in the user agent
        $ua = $this->server->httpRequest->getHeader('User-Agent');
        if (strpos($ua,'iCal/')!==false) {
            throw new Sabre_DAV_Exception_PermissionDenied('iCal has major bugs in it\'s RFC3744 support. Therefore we are left with no other choice but disabling this feature.');
        }

        $body = $this->server->httpRequest->getBody(true);
        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body);

        $properties = array();
        foreach($dom->firstChild->childNodes as $child) {

            if (Sabre_DAV_XMLUtil::toClarkNotation($child)!=='{DAV:}set') continue;
            foreach($this->server->parseProps($child) as $k=>$prop) {
                $properties[$k] = $prop;
            }
        
        }

        $requestUri = $this->server->getRequestUri();

        // We'll check if the parent exists, and if it's a collection. If this is not the case, we need to throw a conflict exception
        try {
            if ($parentNode = $this->server->tree->getNodeForPath(dirname($requestUri))) {
                if (!($parentNode instanceof Sabre_CalDAV_ICalendarCollection)) {
                    throw new Sabre_DAV_Exception_Conflict('Parent node is not a calendar collection');
                }
            }
        } catch (Sabre_DAV_Exception_FileNotFound $e) {

            // This means the parent node doesn't exist, and we need to throw a 409 Conflict
            throw new Sabre_DAV_Exception_Conflict('Parent node does not exist');

        }

        try {
            $node = $this->server->tree->getNodeForPath($requestUri);

            // If we got here.. it means there's already a node on that url, and we need to throw a 405
            throw new Sabre_DAV_Exception_MethodNotAllowed('The directory you tried to create already exists');

        } catch (Sabre_DAV_Exception_FileNotFound $e) {
            // This is correct
        }

        if (!$this->server->broadcastEvent('beforeBind',array($requestUri)));
        $parentNode->createCalendar(basename($requestUri),$properties);
        if (!$this->server->broadcastEvent('afterBind',array($requestUri)));
        $this->server->httpResponse->sendStatus(201);

        
    }

    /**
     * afterGetProperties
     *
     * This method handler is invoked after properties for a specific resource
     * are received. This allows us to add any properties that might have been
     * missing.
     * 
     * @param string $path
     * @param array $properties 
     * @return void
     */
    public function afterGetProperties($path, &$properties) {

        $calHome = '{' . self::NS_CALDAV . '}calendar-home-set';
      
        $currentNode = null;


        // Nasty construct to see if an item exists in an array (it can be null)
        if (array_key_exists($calHome,$properties[404])) {
        
            // TODO: this code is not that great. might be good 
            // to find a better way to do this.
            if (!$currentNode) $currentNode = $this->server->tree->getNodeForPath($path);
            if ($currentNode instanceof Sabre_DAVACL_Principal) {
                $principalId = $currentNode->getName();
                $calendarHomePath = self::CALENDAR_ROOT . '/' . $principalId . '/';
                unset($properties[404][$calHome]);
                $properties[200][$calHome] = new Sabre_DAV_Property_Href($calendarHomePath);
            }
        }

         
        if (array_key_exists('{DAV:}supported-report-set', $properties[200])) {
            if (!$currentNode) $currentNode = $this->server->tree->getNodeForPath($path);
            if ($currentNode instanceof Sabre_CalDAV_ICalendar || $currentNode instanceof Sabre_CalDAV_CalendarObject) {
                $properties[200]['{DAV:}supported-report-set']->addReport(array(
                     '{' . self::NS_CALDAV . '}calendar-multiget',
                     '{' . self::NS_CALDAV . '}calendar-query',
                //     '{' . self::NS_CALDAV . '}supported-collation-set',
                //     '{' . self::NS_CALDAV . '}free-busy-query',
                ));
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

        $properties = array_keys($this->server->parseProps($dom->firstChild));

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

        $requestedProperties = array_keys($this->server->parseProps($dom->firstChild));

        $filterNode = $dom->getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav','filter');
        $filters = $this->parseFilters($filterNode->item(0));

        // Making sure we're always requesting the calendar-data property
        $requestedProperties[] = '{urn:ietf:params:xml:ns:caldav}calendar-data';

        // These are the list of nodes that potentially match the requirement
        $candidateNodes = $this->server->getPropertiesForPath($this->server->getRequestUri(),$requestedProperties,$this->server->getHTTPDepth(0));

        $verifiedNodes = array();

        foreach($candidateNodes as $node) {

            // If the node didn't have a calendar-data property, it must not be a calendar object
            if (!isset($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'])) continue;

            if ($this->validateFilters($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'],$filters)) {
                $verifiedNodes[] = $node;
            } 

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($verifiedNodes));

    }


    protected function parseFilters($domNode) {

        $filters = array();

        foreach($domNode->childNodes as $child) {

            switch(Sabre_DAV_XMLUtil::toClarkNotation($child)) {

                case '{urn:ietf:params:xml:ns:caldav}comp-filter' :
                    
                    $filters[] = array(
                        'type' => self::FILTER_COMPFILTER, 
                        'name' => $child->getAttribute('name'),
                        'filters' => $this->parseFilters($child),
                    );
                    break;

                case '{urn:ietf:params:xml:ns:caldav}is-not-defined' :
                
                    $filters[] = array(
                        'type' => self::FILTER_ISNOTDEFINED,
                    );
                    break;

                case '{urn:ietf:params:xml:ns:caldav}time-range' :
                
                    $filters[] = array(
                        'type'  => self::FILTER_TIMERANGE,
                        'start' => $child->getAttribute('start'),
                        'end'   => $child->getAttribute('end'),
                    );
                    break;

                case '{urn:ietf:params:xml:ns:caldav}prop-filter' :
                
                    $filters[] = array(
                        'type'  => self::FILTER_PROPFILTER,
                        'name' => $child->getAttribute('name'),
                        'filters' => $this->parseFilters($child),
                    );
                    break;

                case '{urn:ietf:params:xml:ns:caldav}param-filter' :
                
                    $filters[] = array(
                        'type'  => self::FILTER_PARAMFILTER,
                        'name' => $child->getAttribute('name'),
                        'filters' => $this->parseFilters($child),
                    );
                    break;

                case '{urn:ietf:params:xml:ns:caldav}text-match' :
               
                    $collation = $child->getAttribute('collation');
                    if (!$collation) $collation = 'i;ascii-casemap';

                    $filters[] = array(
                        'type'  => self::FILTER_TEXTMATCH,
                        'collation' => $child->getAttribute('collation'),
                        'negate-condition' => $child->getAttribute('negate-condition'),
                        'value' => $child->nodeValue,
                    );
                    break;

            }

        }

        return $filters;

    }

    protected function validateFilters($calendarData,$filters) {

        // We are converting the calendar object to an XML structure
        // This makes it far easier to parse
        $xCalendarData = Sabre_CalDAV_ICalendarToXML::toXML($calendarData);
        $xml = simplexml_load_string($xCalendarData);
        $xml->registerXPathNamespace('c','urn:ietf:params:xml:ns:xcal');
        return $this->validateXMLFilters($xml,$filters);
        
    }

    protected function validateXMLFilters($xNode,$filters,$xpath = '/c:iCalendar') {

        foreach($filters as $filter) {

            switch($filter['type']) {

                case self::FILTER_COMPFILTER :
                case self::FILTER_PROPFILTER :

                    $xpath.='/c:' . strtolower($filter['name']);
                    // First we'll check if there's a is-not-defined
                    $isNotDefined = false;

                    foreach($filter['filters'] as $subfilter) {
                        if ($subfilter['type']==self::FILTER_ISNOTDEFINED) {
                            $isNotDefined = true;
                            break;
                        }
                    }
                    if ($isNotDefined) {
                        if($xNode->xpath($xpath))
                            return false; // Node did exist. Filter failed 
                        else
                            break; // Node did not exist, mov on to next filter
                    }

                    if(!($subNode = $xNode->xpath($xpath)))
                        return false; // This node did not exist, Filter failed

                    // Validating subfilters
                    if(!$this->validateXMLFilters($xNode,$filter['filters'],$xpath))
                        return false;

                    break;

                case self::FILTER_TIMERANGE :
                    // TODO
                    break;

                case self::FILTER_PARAMFILTER :
                    // TODO
                    break;

                case self::FILTER_TEXTMATCH :
                    list($string) = $xNode->xpath($xpath);
                    $string = (string)$string;
                    switch($filter['collation']) {
                        case 'i;ascii-casemap' :
                            if (mb_strpos(mb_strtolower($string),mb_strtolower($filter['value']))===false)
                                return false;
                            else 
                                break 2;

                        case 'i;octet' :
                            if (strpos($string,$filter['value']) === false)
                                return false;
                            else
                                break 2;
                    }
                    break;

            } // end of filter switch

        } // end of filter foreach

        return true;

    }

}
