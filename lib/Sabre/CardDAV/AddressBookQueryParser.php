<?php

/**
 * Parses the addressbook-query report request body.
 *
 * Whoever designed this format, and the CalDAV equavalent even more so, 
 * has no feel for design.
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CardDAV_AddressBookQueryParser {

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
        $this->xpath->registerNameSpace('card',self::NS_CARDDAV);

    }

    /**
     * Parses the request. 
     * 
     * @param DOMNode $dom 
     * @return void
     */
    public function parse(DOMNode $dom) {

        $root = $dom->firstChild;
        $filterNode = null;
        
        $limit = $this->xpath->evaluate('number(/card:limit/card:nresults)');
        if (!$limit) $limit = null;

        $filter = $this->xpath->query('/card:addressbook-query/card:filter');
        if ($filter->length !== 1) {
            throw new Sabre_DAV_Exception_BadRequest('Only one filter element is allowed');
        }

        $filter = $filter->item(0);
        $test = $this->xpath->evaluate('string(@test)', $filter);
        if (!$test) $test = 'anyof';
        if ($test !== 'anyof' && $test !== 'allof') {
            throw new Sabre_DAV_Exception_BadRequest('The test attribute must either hold "anyof" or "allof"');
        }

        $propFilters = array();

        $propFilterNodes = $this->xpath->query('card:prop-filter', $filter);
        for($ii=0; $ii < $propFilterNodes->length; $ii++) {

            $propFilters[] = $this->parsePropFilterNode($propFilterNodes->item($ii));


        }

        return $propFilters;

    }

    /**
     * Parses the prop-filter xml element
     * 
     * @param DOMElement $propFilterNode 
     * @return array 
     */
    protected function parsePropFilterNode(DOMElement $propFilterNode) {

        $propFilter = array();
        $propFilter['name'] = $propFilterNode->getAttribute('name');
        $propFilter['test'] = $propFilterNode->getAttribute('test');
        if (!$propFilter['test']) $propFilter['test'] = 'anyof';

        $propFilter['is-not-defined'] = $this->xpath->evaluate('element-available(card:is-not-defined)', $propFilterNode);

        $paramFilterNodes = $this->xpath->query('card:param-filter', $propFilterNode);

        $propFilter['param-filters'] = array();


        for($ii=0;$ii<$paramFilterNodes->length;$ii++) {

            $propFilter['param-filters'][] = $this->parseParamFilterNode($paramFiltersNodes->item($ii));

        }
        $propFilter['text-matches'] = array();
        $textMatchNodes = $this->xpath->query('card:text-match', $propFilterNode);

        for($ii=0;$ii<$textMatchNodes->length;$ii++) {

            $propFilter['text-matches'][] = $this->parseTextMatchNode($textMatchNodes->item($ii));

        }

        return $propFilter;

    }

    /**
     * Parses the param-filter element 
     * 
     * @param DOMElement $paramFilterNode 
     * @return array 
     */
    public function parseParamFilterNode(DOMElement $paramFilterNode) {
        
        $paramFilter = array();
        $paramFilter['name'] = $paramFilterNode->getAttribute('name');
        $paramFilter['is-not-defined'] = $this->xpath->evaluate('element-available(card:is-not-defined)', $paramFilterNode);
        $paramFilter['text-match'] = null;

        $textMatch = $this->xpath->evaluate('text-match[1]');
        if ($textMatch) {
            $paramFilter['text-match'] = $this->parseTextMatchNode($textMatch);
        } 

    }

    /**
     * Text match
     * 
     * @param DOMElement $textMatchNode 
     * @return void
     */
    public function parseTextMatchNode(DOMElement $textMatchNode) {

        $matchType = $textMatchNode->getAttribute('match-type');
        if (!$matchType) $matchType = 'contains';

        if (!in_array($matchType, array('contains', 'equals', 'starts-with', 'ends-with'))) {
            throw new Sabre_DAV_Exception_BadRequest('Unknown match-type: ' . $matchType);
        }

        $negateCondition = $textMatchNode->getAttribute('negate-condition');
        $negateCondition = $negateCondition==='yes';
        $collation = $textMatchNode->getAttribute('collation');
        if (!$collation) $collation = 'i;unicode-casemap';

        return array(
            'negate-condition' => $negateCondition,
            'collation' => $collation,
            'match-type' => $matchType,
            'value' => $textMatchNode->nodeValue
        );
          

    } 

}
