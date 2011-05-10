<?php

/**
 * CardDAV plugin 
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */


/**
 * The CardDAV plugin adds CardDAV functionality to the WebDAV server
 */
class Sabre_CardDAV_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Url to the addressbooks
     */
    const ADDRESSBOOK_ROOT = 'addressbooks';

    /**
     * xml namespace for CardDAV elements
     */
    const NS_CARDDAV = 'urn:ietf:params:xml:ns:carddav';

    /**
     * Server class 
     * 
     * @var Sabre_DAV_Server 
     */
    protected $server;

    /**
     * Initializes the plugin 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void 
     */
    public function initialize(Sabre_DAV_Server $server) {

        /* Events */
        $server->subscribeEvent('afterGetProperties', array($this, 'afterGetProperties'));
        $server->subscribeEvent('report', array($this,'report'));

        /* Namespaces */
        $server->xmlNamespaces[self::NS_CARDDAV] = 'card';

        $this->server = $server;

    }

    /**
     * Returns a list of supported features.
     *
     * This is used in the DAV: header in the OPTIONS and PROPFIND requests. 
     * 
     * @return array
     */
    public function getFeatures() {

        return array('addressbook');

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
        if ($node instanceof Sabre_CardDAV_AddressBook || $node instanceof Sabre_CardDAV_Card) {
            return array(
                 '{' . self::NS_CARDDAV . '}addressbook-multiget',
            );
        }
        return array();

    }


    /**
     * Adds all CardDAV-specific properties 
     * 
     * @param string $path 
     * @param array $properties 
     * @return void
     */
    public function afterGetProperties($path, array &$properties) { 

        // Find out if we are currently looking at a principal resource
        $currentNode = $this->server->tree->getNodeForPath($path);
        if ($currentNode instanceof Sabre_DAVACL_IPrincipal) {

            // calendar-home-set property
            $addHome = '{' . self::NS_CARDDAV . '}addressbook-home-set';
            if (array_key_exists($addHome,$properties[404])) {
                $principalId = $currentNode->getName(); 
                $addressbookHomePath = self::ADDRESSBOOK_ROOT . '/' . $principalId . '/';
                unset($properties[404][$addHome]);
                $properties[200][$addHome] = new Sabre_DAV_Property_Href($addressbookHomePath);
            }

        }

    }

    /**
     * This functions handles REPORT requests specific to CardDAV 
     * 
     * @param string $reportName 
     * @param DOMNode $dom 
     * @return bool 
     */
    public function report($reportName,$dom) {

        switch($reportName) { 
            case '{'.self::NS_CARDDAV.'}addressbook-multiget' :
                $this->addressbookMultiGetReport($dom);
                return false;
            default :
                return;

        }


    }

    /**
     * This function handles the addressbook-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     * 
     * @param DOMNode $dom 
     * @return void
     */
    public function addressbookMultiGetReport($dom) {

        $properties = array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild));

        $hasAddressData = false;
        $addressDataElem = '{' . self::NS_CARDDAV . '}address-data';
        if (in_array($addressDataElem, $properties)) {
            $hasAddressData = true;
            unset($properties[$addressDataElem]);
        }

        $hrefElems = $dom->getElementsByTagNameNS('urn:DAV','href');
        foreach($hrefElems as $elem) {
            $uri = $this->server->calculateUri($elem->nodeValue);
            list($objProps) = $this->server->getPropertiesForPath($uri,$properties);

            // This needs to be fetched using get()
            if ($hasAddressData) {

                $node = $this->server->tree->getNodeForPath($uri);
                $objProps[200][$addressDataElem] = stream_get_contents($node->get());

            }
            $propertyList[]=$objProps;

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($propertyList));

    }
}
