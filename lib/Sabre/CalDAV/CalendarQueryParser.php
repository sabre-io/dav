<?php

/**
 * Parses the calendar-query report request body.
 *
 * Whoever designed this format, and the CalDAV equavalent even more so, 
 * has no feel for design.
 * 
 * @package Sabre
 * @subpackage CalDAV 
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_CalendarQueryParser {

    /**
     * List of requested properties the client wanted
     * 
     * @var array 
     */
    public $requestedProperties;

    /**
     * List of property/component filters.
     *
     * @var array 
     */
    public $filters;

    /**
     * DOM Document
     * 
     * @var DOMDocument 
     */
    protected $dom;

    /**
     * DOM XPath object 
     * 
     * @var DOMXPath 
     */
    protected $xpath;

    /**
     * Creates the parser
     * 
     * @param DOMNode $dom 
     * @return void
     */
    public function __construct(DOMDocument $dom) {

        $this->dom = $dom;

        $this->xpath = new DOMXPath($dom);
        $this->xpath->registerNameSpace('cal',Sabre_CalDAV_Plugin::NS_CALDAV);

    }

    /**
     * Parses the request. 
     * 
     * @param DOMNode $dom 
     * @return void
     */
    public function parse() {

        $filterNode = null;

        $filter = $this->xpath->query('/cal:calendar-query/cal:filter');
        if ($filter->length !== 1) {
            throw new Sabre_DAV_Exception_BadRequest('Only one filter element is allowed');
        }

        $compFilters = $this->parseCompFilters($filter->item(0)); 
        if (count($compFilters)!==1) {
            throw new Sabre_DAV_Exception_BadRequest('There must be exactly 1 top-level comp-filter.');
        }

        $this->filters = $compFilters[0];
        $this->requestedProperties = array_keys(Sabre_DAV_XMLUtil::parseProperties($this->dom->firstChild));

    }

    /**
     * Parses all the 'comp-filter' elements from a node
     *
     * @param DOMElement $parentNode 
     * @return array 
     */
    protected function parseCompFilters(DOMElement $parentNode) {

        $compFilterNodes = $this->xpath->query('cal:comp-filter', $parentNode);
        $result = array();

        for($ii=0; $ii < $compFilterNodes->length; $ii++) {

            $compFilterNode = $compFilterNodes->item($ii);

            $compFilter = array();
            $compFilter['name'] = $compFilterNode->getAttribute('name');
            $compFilter['is-not-defined'] = $this->xpath->query('cal:is-not-defined', $compFilterNode)->length>0;
            $compFilter['comp-filters'] = $this->parseCompFilters($compFilterNode);
            $compFilter['prop-filters'] = $this->parsePropFilters($compFilterNode);
            $compFilter['time-range'] = $this->parseTimeRange($compFilterNode);

            $result[] = $compFilter;

        }

        return $result;

    }

    /**
     * Parses all the prop-filter elements from a node 
     * 
     * @param DOMElement $parentNode
     * @return array 
     */
    protected function parsePropFilters(DOMElement $parentNode) {

        $propFilterNodes = $this->xpath->query('cal:prop-filter', $parentNode);
        $result = array();

        for ($ii=0; $ii < $propFilterNodes->length; $ii++) {

            $propFilterNode = $propFilterNodes->item($ii);
            $propFilter = array();
            $propFilter['name'] = $propFilterNode->getAttribute('name');
            $propFilter['is-not-defined'] = $this->xpath->query('cal:is-not-defined', $propFilterNode)->length>0;
            $propFilter['param-filters'] = $this->parseParamFilters($propFilterNode);
            $propFilter['text-match'] = $this->parseTextMatch($propFilterNode);
            $propFilter['time-range'] = $this->parseTimeRange($propFilterNode);

            $result[] = $propFilter;

        }

        return $result;

    }

    /**
     * Parses the param-filter element 
     * 
     * @param DOMElement $paramFilterNode 
     * @return array 
     */
    public function parseParamFilters(DOMElement $parentNode) {

        $paramFilterNodes = $this->xpath->query('cal:param-filter', $parentNode);
        $result = array();

        for($ii=0;$ii<$paramFilterNodes->length;$ii++) {

            $paramFilterNode = $paramFilterNodes->item($ii);
            $paramFilter = array();
            $paramFilter['name'] = $paramFilterNode->getAttribute('name');
            $paramFilter['is-not-defined'] = $this->xpath->query('cal:is-not-defined', $paramFilterNode)->length>0;
            $paramFilter['text-match'] = $this->parseTextMatch($paramFilterNode);

            $result[] = $paramFilter;

        }

        return $result;

    }

    /**
     * Parses the text-match element 
     * 
     * @param DOMElement $parentNode 
     * @return array|null 
     */
    public function parseTextMatch(DOMElement $parentNode) {

        $textMatchNodes = $this->xpath->query('cal:text-match', $parentNode);

        if ($textMatchNodes->length === 0)
            return null;

        $textMatchNode = $textMatchNodes->item(0);
        $negateCondition = $textMatchNode->getAttribute('negate-condition');
        $negateCondition = $negateCondition==='yes';
        $collation = $textMatchNode->getAttribute('collation');
        if (!$collation) $collation = 'i;ascii-casemap';

        return array(
            'negate-condition' => $negateCondition,
            'collation' => $collation,
            'value' => $textMatchNode->nodeValue
        );

    }

    /**
     * Parses the time-range element 
     * 
     * @param DOMElement $parentNode 
     * @return array|null 
     */
    public function parseTimeRange(DOMElement $parentNode) {

        $timeRangeNodes = $this->xpath->query('cal:time-range', $parentNode);
        if ($timeRangeNodes->length === 0) {
            return null;
        }

        $timeRangeNode = $timeRangeNodes->item(0);

        if ($start = $timeRangeNode->getAttribute('start')) {
            $start = Sabre_VObject_DateTimeParser::parseDateTime($start);
        } else {
            $start = null;
        }
        if ($end = $timeRangeNode->getAttribute('end')) {
            $end = Sabre_VObject_DateTimeParser::parseDateTime($end);
        } else {
            $end = null;
        }

        if (!is_null($start) && !is_null($end) && $end <= $start) {
            throw new Sabre_DAV_Exception_BadRequest('The end-date must be larger than the start-date in the time-range filter');
        }

        return array(
            'start' => $start,
            'end' => $end,
        );

    }

}
