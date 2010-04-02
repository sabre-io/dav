<?php

/**
 * This plugin provides Authentication for a WebDAV server.
 * 
 * It relies on a Backend object, which provides user information.
 *
 * Additionally, it provides support for the RFC 5397 current-user-principal
 * property.
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Reference to main server object 
     * 
     * @var Sabre_DAV_Server 
     */
    private $server;

    /**
     * Authentication backend
     * 
     * @var Sabre_DAV_Auth_Backend_Abstract 
     */
    private $authBackend;

    /**
     * The authentication realm. 
     * 
     * @var string 
     */
    private $realm;

    /**
     * userName of currently logged in user 
     * 
     * @var string 
     */
    private $userInfo;

    /**
     * __construct 
     * 
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     * @param string $realm 
     * @return void
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend, $realm) {

        $this->authBackend = $authBackend;
        $this->realm = $realm;

    }

    /**
     * Initializes the plugin. This function is automatically called by the server  
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $this->server->subscribeEvent('beforeMethod',array($this,'beforeMethod'),10);
        $this->server->subscribeEvent('afterGetProperties',array($this,'afterGetProperties'));
        $this->server->subscribeEvent('report',array($this,'report'));

    }

    /**
     * Returns the currently logged in user's information.
     *
     * This will only be set if authentication was succesful.
     * 
     * @return array 
     */
    public function getUserInfo() {

        return $this->userInfo;

    }

    /**
     * This method intercepts calls to PROPFIND and similar lookups 
     * 
     * This is done to inject the current-user-principal if this is requested.
     *
     * @todo support for 'unauthenticated'
     * @return void  
     */
    public function afterGetProperties($href, &$properties) {

        if (array_key_exists('{DAV:}current-user-principal', $properties[404])) {
            if ($this->userInfo) {
                $properties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::HREF, 'principals/' . $this->userInfo['userId']);
            } else {
                $properties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::UNAUTHENTICATED);
            }
            unset($properties[404]['{DAV:}current-user-principal']);
        }
        if (array_key_exists('{DAV:}supported-report-set', $properties[200])) {
            $properties[200]['{DAV:}supported-report-set']->addReport(array(
                '{DAV:}expand-property',
            ));
        }

    }

    /**
     * This method is called before any HTTP method and forces users to be authenticated
     * 
     * @param string $method
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool 
     */
    public function beforeMethod($method) {

        $userInfo = $this->authBackend->authenticate($this->server,$this->realm);
        if ($userInfo===false) throw new Sabre_DAV_Exception_NotAuthenticated('Incorrect username or password, or no credentials provided');
        if (!is_array($userInfo)) throw new Sabre_DAV_Exception('The authenticate method must either return an array, or false');

        $this->userInfo = $userInfo;
    }

    /**
     * This functions handles REPORT requests 
     * 
     * @param string $reportName 
     * @param DOMNode $dom 
     * @return bool 
     */
    public function report($reportName,$dom) {

        switch($reportName) { 
            case '{DAV:}expand-property' :
                return $this->expandPropertyReport($dom);

        }
    
    }

    /**
     * The expand-property report is defined in RFC3253 section 3-8. 
     *
     * This report is very similar to a standard PROPFIND. The difference is
     * that it has the additional ability to look at properties containing a
     * {DAV:}href element, follow that property and grab additional elements
     * there.
     *
     * Other rfc's, such as ACL rely on this report, so it made sense to put
     * it in this plugin.
     *
     * @param DOMElement $dom 
     * @return void
     */
    protected function expandPropertyReport($dom) {

        $requestedProperties = $this->parseExpandPropertyReportRequest($dom->firstChild->firstChild);
        $depth = $this->server->getHTTPDepth(0);
        $requestUri = $this->server->getRequestUri();

        $result = $this->expandProperties($requestUri,$requestedProperties,$depth);

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');
        $dom->appendChild($multiStatus);

        // Adding in default namespaces
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $multiStatus->setAttribute('xmlns:' . $prefix,$namespace);

        }

        foreach($result as $entry) {

            $entry->serialize($this->server,$multiStatus);

        }

        $xml = $dom->saveXML();
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($xml);

        // Make sure the event chain is broken
        return false;

    }

    /**
     * This method is used by expandPropertyReport to parse
     * out the entire HTTP request.
     * 
     * @param DOMElement $node 
     * @return array 
     */
    protected function parseExpandPropertyReportRequest($node) {

        $requestedProperties = array();
        do {

            if (Sabre_DAV_XMLUtil::toClarkNotation($node)!=='{DAV:}property') continue;
                
            if ($node->firstChild) {
                
                $children = $this->parseExpandPropertyReportRequest($node->firstChild);

            } else {

                $children = array();

            }

            $namespace = $node->getAttribute('namespace');
            if (!$namespace) $namespace = 'DAV:';

            $propName = '{'.$namespace.'}' . $node->getAttribute('name');
            $requestedProperties[$propName] = $children; 

        } while ($node = $node->nextSibling);

        return $requestedProperties;

    }

    /**
     * This method expands all the properties and returns
     * a list with property values
     *
     * @param array $path
     * @param array $requestedProperties the list of required properties
     * @param array $depth
     */
    protected function expandProperties($path,array $requestedProperties,$depth) { 

        $foundProperties = $this->server->getPropertiesForPath($path,array_keys($requestedProperties),$depth);

        $result = array();

        foreach($foundProperties as $node) {

            foreach($requestedProperties as $propertyName=>$childRequestedProperties) {

                // We're only traversing if sub-properties were requested
                if(count($childRequestedProperties)===0) continue;
                
                // We only have to do the expansion if the property was found
                // and it contains an href element.
                if (!array_key_exists($propertyName,$node[200])) continue;
                if (!($node[200][$propertyName] instanceof Sabre_DAV_Property_IHref)) continue;

                $href = $node[200][$propertyName]->getHref();
                list($node[200][$propertyName]) = $this->expandProperties($href,$childRequestedProperties,0);

            }
            $result[] = new Sabre_DAV_Property_Response($path, $node);

        }

        return $result;

    }


}
