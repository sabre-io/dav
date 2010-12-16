<?php

/**
 * SabreDAV ACL Plugin
 *
 * This plugin provides funcitonality to enforce ACL permissions.
 * ACL is defined in RFC3744.
 *
 * In addition it also provides support for the {DAV:}current-user-principal 
 * property, defined in RFC5397 and the {DAV:}expand-properties report, as 
 * defined in RFC3253. 
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Recursion constants
     *
     * This only checks the base node
     */
    const R_PARENT = 1;

    /**
     * Recursion constants
     *
     * This checks every node in the tree
     */
    const R_RECURSIVE = 2;

    /**
     * Recursion constants
     *
     * This checks every parentnode in the tree, but not leaf-nodes.
     */
    const R_RECURSIVEPARENTS = 3;

    /**
     * Reference to server object. 
     * 
     * @var Sabre_DAV_Server 
     */
    protected $server;

    /**
     * List of urls containing principal collections.
     * Modify this if your principals are located elsewhere. 
     * 
     * @var array
     */
    public $principalCollectionSet = array(
        'principals',
    );

    /**
     * By default the user van only access nodes that have built-in support for 
     * ACL (though the Sabre_DAVACL_IACL interface). When nodes are encountered 
     * that don't implement this interface, access is denied.
     *
     * This is useful for some setups, but not for others. This setting allows 
     * @var bool 
     */
    public $allowAccessToNodesWithoutACL = false;

    /**
     * Returns a list of features added by this plugin.
     *
     * This list is used in the response of a HTTP OPTIONS request.
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('access-control');

    }

    /**
     * Returns a list of available methods for a given url 
     * 
     * @param string $uri 
     * @return array 
     */
    public function getMethods($uri) {

        // TODO: needs to support ACL.
        return array();

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

        return 'acl';

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

        return array(
            '{DAV:}expand-properties',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set', 
        );

    }


    /**
     * Checks if the current user has the specified privilege(s). 
     * 
     * You can specify a single privilege, or a list of privileges.
     * This method will throw an exception if the privilege is not available
     * and return true otherwise.
     *
     * @param string $uri
     * @param array|string $privileges
     * @throws Sabre_DAVACL_Exception_NeedPrivileges
     * @return bool 
     */
    public function checkPrivileges($uri,$privileges,$recursion = self::R_PARENT) {

        if (!is_array($privileges)) $privileges = array($privileges);

        $acl = $this->getCurrentUserPrivilegeSet($uri);

        if (is_null($acl)) {
            if ($this->allowAccessToNodesWithoutACL) {
                return true;
            } else {
                throw new Sabre_DAVACL_Exception_NeedPrivileges($uri,$privileges);
            }
        }

        // Now we need to figure out per-privilege what it's 'real' concrete 
        // privilege is, and see if it's in the ACL list.

        $list = $this->getFlatPrivileges();
        $failed = array();

        foreach($privileges as $priv) {

            if (!isset($list[$priv])) {
                $failed[] = $priv;
                continue;
            }

            $concrete = $list[$priv]['concrete'];

            foreach($acl as $ace) {
                if ($ace['privilege'] == $concrete) {
                    continue 2;
                }
            }

            $failed[] = $priv; 

        }

        if ($failed) {
            throw new Sabre_DAVACL_Exception_NeedPrivileges($uri,$failed);
        }
        return true;

    }

    /**
     * Returns the standard users' principal.
     *
     * This is one authorative principal url for the current user. 
     * 
     * @return string 
     */
    public function getCurrentUserPrincipal() {

        $authPlugin = $this->server->getPlugin('auth');
        if (is_null($authPlugin)) return null;

        return $authPlugin->getCurrentUserPrincipal();

    }

    /**
     * Returns a list of principals that's associated to the current
     * user, either directly or through group membership. 
     * 
     * @return array 
     */
    public function getCurrentUserPrincipals() {

        $currentUser = $this->getCurrentUserPrincipal();

        if (is_null($currentUser)) return array();

        $check = array($currentUser);
        $principals = array($currentUser);

        while(count($check)) {

            $principal = array_shift($check);
 
            $node = $this->server->tree->getNodeForPath($principal);
            if ($node instanceof Sabre_DAVACL_IPrincipal) {
                foreach($node->getGroupMembership() as $groupMember) {

                    if (!in_array($groupMember, $principals)) {

                        $check[] = $groupMember;
                        $principals[] = $groupMember;

                    }

                }

            }

        }

        return $principals;

    }

    /**
     * Returns the supported privilege structure for this ACL plugin.
     *
     * See RFC3744 for more details. Currently we default on a simple,
     * standard structure. 
     * 
     * @return array 
     */
    public function getSupportedPrivilegeSet() {

        return array(
            'privilege'  => '{DAV:}all',
            'abstract'   => true,
            'aggregates' => array(
                array(
                    'privilege'  => '{DAV:}read',
                    'aggregates' => array(
                        array(
                            'privilege' => '{DAV:}read-acl',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}read-current-user-privilege-set',
                            'abstract'  => true,
                        ),
                    ),
                ), // {DAV:}read
                array(
                    'privilege'  => '{DAV:}write',
                    'aggregates' => array(
                        array(
                            'privilege' => '{DAV:}write-acl',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}write-properties',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}write-content',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}unlock',
                            'abstract'  => true,
                        ),
                    ),
                ), // {DAV:}write
            ), 
        ); // {DAV:}all

    }

    /**
     * Returns the supported privilege set as a flat list
     *
     * This is much easier to parse.
     *
     * The returned list will be index by privilege name.
     * The value is a struct containing the following properties:
     *   - aggregates
     *   - abstract
     *   - concrete
     * 
     * @return array 
     */
    final public function getFlatPrivilegeSet() {

        $privs = $this->getSupportedPrivilegeSet();

        $flat = array();

        $traverse = null;
        $traverse = function($priv, $concrete = null) use (&$flat, &$traverse) {

            $myPriv = array(
                'abstract' => isset($priv['abstract']) && $priv['abstract'],
                'aggregates' => array(),
                'concrete' => isset($priv['abstract']) && $priv['abstract']?$concrete:$priv['privilege'],
            );

            if (isset($priv['aggregates']))
                foreach($priv['aggregates'] as $subPriv) $myPriv['aggregates'][] = $subPriv['privilege'];

            $flat[$priv['privilege']] = $myPriv;

            if (isset($priv['aggregates']))
                foreach($priv['aggregates'] as $subPriv) {
                
                    $traverse($subPriv,$myPriv['concrete']);

                }

        };

        $traverse($privs);

        return $flat;

    }

    /**
     * Returns the full ACL list.
     *
     * Either a uri or a Sabre_DAV_INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs. 
     * 
     * @param string|Sabre_DAV_INode $node
     * @return array
     */
    public function getACL($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }
        if ($node instanceof Sabre_DAVACL_IACL) {
            return $node->getACL();
        }
        return null; 

    }

    /**
     * Returns a list of privileges the current user has
     * on a particular node.
     *
     * Either a uri or a Sabre_DAV_INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs. 
     * 
     * @param string|Sabre_DAV_INode $node 
     * @return array 
     */
    public function getCurrentUserPrivilegeSet($node) {

        $acl = $this->getACL($node);
        if (is_null($acl)) return null;

        $principals = $this->getCurrentUserPrincipals();

        $collected = array();

        foreach($acl as $ace) {

            if (in_array($ace['principal'], $principals)) {
                $collected[] = $ace;
            }

        }

        return $collected;

    }

    /**
     * Sets up the plugin
     *
     * This method is automatically called by the server class.
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));

        $server->subscribeEvent('beforeMethod', array($this,'beforeMethod'),20);
        $server->subscribeEvent('beforeBind', array($this,'beforeBind'),20);
        $server->subscribeEvent('beforeUnbind', array($this,'beforeUnbind'),20);
        $server->subscribeEvent('afterGetProperties', array($this,'afterGetProperties'),220);
        $server->subscribeEvent('beforeUnlock', array($this,'beforeUnlock'),20);
        $server->subscribeEvent('report',array($this,'report'));

        array_push($server->protectedProperties,
            '{DAV:}alternate-URI-set',
            '{DAV:}principal-URL',
            '{DAV:}group-membership',
            '{DAV:}principal-collection-set',
            '{DAV:}current-user-principal',
            '{DAV:}supported-privilege-set',
            '{DAV:}current-user-privilege-set',
            '{DAV:}acl',
            '{DAV:}acl-restrictions',
            '{DAV:}inherited-acl-set'
        );

    }


    /* {{{ Event handlers */

    /**
     * Triggered before any method is handled 
     * 
     * @param string $method 
     * @param string $uri 
     * @return void
     */
    public function beforeMethod($method, $uri) {

        $exists = $this->server->tree->nodeExists($uri);

        // If the node doesn't exists, none of these checks apply
        if (!$exists) return;

        switch($method) {

            case 'GET' :
            case 'HEAD' :
            case 'OPTIONS' :
                // For these 3 we only need to know if the node is readable.
                $this->checkPrivileges($uri,'{DAV:}read');
                break;

            case 'PUT' :
            case 'LOCK' :
                // This method requires the write-content priv if the node 
                // already exists, and bind on the parent if the node is being 
                // created. 
                // The bind privilege is handled in the beforeBind event. 
                $this->checkPrivileges($uri,'{DAV:}write-content');
                break;
            

            case 'PROPPATCH' :
                $this->checkPrivileges($uri,'{DAV:}write-properties');
                break;

            case 'ACL' :
                $this->checkPrivileges($uri,'{DAV:}write-acl');
                break;

            case 'COPY' :
            case 'MOVE' :
                // Copy requires read privileges on the entire source tree.
                // If the target exists write-content normally needs to be 
                // checked, however, we're deleting the node beforehand and 
                // creating a new one after, so this is handled by the 
                // beforeUnbind event.
                // 
                // The creation of the new node is handled by the beforeBind 
                // event.
                //
                // If MOVE is used beforeUnbind will also be used to check if 
                // the sourcenode can be deleted. 
                $this->checkPrivileges($uri,'{DAV:}read',self::R_RECURSIVE);

                break;

        }

    }

    /**
     * Triggered before a new node is created.
     * 
     * This allows us to check permissions for any operation that creates a
     * new node, such as PUT, MKCOL, MKCALENDAR, LOCK, COPY and MOVE.
     * 
     * @param string $uri 
     * @return void
     */
    public function beforeBind($uri) {

        list($parentUri,$nodeName) = Sabre_DAV_URLUtil::splitPath($uri);
        $this->checkPrivileges($parentUri,'{DAV:}bind');

    }

    /**
     * Triggered before a node is deleted 
     * 
     * This allows us to check permissions for any operation that will delete 
     * an existing node. 
     * 
     * @param string $uri 
     * @return void
     */
    public function beforeUnbind($uri) {

        list($parentUri,$nodeName) = Sabre_DAV_URLUtil::splitPath($uri);
        $this->checkPrivileges($parentUri,'{DAV:}unbind',self::R_RECURSIVEPARENTS);

    }

    /**
     * Triggered after all properties are received.
     * 
     * This allows us to deny access to any properties if there's no
     * permission to grab them. 
     * 
     * @param string $uri 
     * @param array $properties 
     * @return void
     */
    public function afterGetProperties($uri, array &$properties) {

        try {

            $this->checkPrivileges($uri,'{DAV:}read');
    
        } catch (Sabre_DAVACL_Exception_NeedPrivileges $e) {

            // Access to properties was denied
            
            if (!isset($properties[403])) $properties[403] = array();
            foreach($properties as $httpStatus=>$propList) {

                // The odd one out
                if ($httpStatus === 'href') continue;

                // No need to do anything if they are already 403
                if ($httpStatus == 403) continue;

                foreach($propList as $propName=>$propValue) {

                    $properties[403][$propName] = null;

                }

                unset($properties[$httpStatus]); 

            }
            return;

        }

        // We need to do specific checks for the {DAV:}acl and 
        // {DAV:}current-user-privilege-set properties.
        if (isset($properties[200]['{DAV:}acl'])) {

            try {

                $this->checkPrivileges($uri,'{DAV:}read-acl');

            } catch (Sabre_DAVACL_Exception_NeedPrivileges $e) {

                if (!isset($properties[403])) $properties[403] = array();

                $properties[403]['{DAV:}acl'] = null;
                unset($properties[200]['{DAV:}acl']);

            }

        }
        if (isset($properties[200]['{DAV:}read-current-user-privilege-set'])) {

            try {

                $this->checkPrivileges($uri,'{DAV:}read-acl');

            } catch (Sabre_DAVACL_Exception_NeedPrivileges $e) {

                if (!isset($properties[403])) $properties[403] = array();

                $properties[403]['{DAV:}acl'] = null;
                unset($properties[200]['{DAV:}acl']);

            }

        }


    }

    /**
     * Triggered before a node is unlocked. 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lock
     * @param TODO: not yet implemented 
     * @return void
     */
    public function beforeUnlock($uri, Sabre_DAV_Locks_LockInfo $lock) {
           

    }

    /**
     * Triggered before properties are looked up in specific nodes. 
     * 
     * @param string $uri 
     * @param Sabre_DAV_INode $node 
     * @param array $requestedProperties 
     * @param array $returnedProperties 
     * @return void
     */
    public function beforeGetProperties($uri, Sabre_DAV_INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof Sabre_DAVACL_IPrincipal) {

            if (false !== ($index = array_search('{DAV:}alternate-URI-set', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}alternate-URI-set'] = new Sabre_DAV_Property_HrefList($node->getAlternateUriSet());

            }
            if (false !== ($index = array_search('{DAV:}principal-URL', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}principal-URL'] = new Sabre_DAV_Property_Href($node->getPrincipalUrl());

            }
            if (false !== ($index = array_search('{DAV:}group-member-set', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}group-member-set'] = new Sabre_DAV_Property_HrefList($node->getGroupMemberSet());

            }
            if (false !== ($index = array_search('{DAV:}group-membership', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}group-membership'] = new Sabre_DAV_Property_HrefList($node->getGroupMembership());

            }

            if (false !== ($index = array_search('{DAV:}resourcetype', $requestedProperties))) {

                $returnedProperties[200]['{DAV:}resourcetype'] = new Sabre_DAV_Property_ResourceType('{DAV:}principal');

            }

        }
        if (false !== ($index = array_search('{DAV:}principal-collection-set', $requestedProperties))) {

            unset($requestedProperties[$index]);
            $returnedProperties[200]['{DAV:}principal-collection-set'] = new Sabre_DAV_Property_HrefList($this->principalCollectionSet);

        }
        if (false !== ($index = array_search('{DAV:}current-user-principal', $requestedProperties))) {

            unset($requestedProperties[$index]);
            if ($url = $this->getCurrentUserPrincipal()) {
                $returnedProperties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::HREF, $url);
            } else {
                $returnedProperties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::UNAUTHENTICATED);
            }

        }
        if (false !== ($index = array_search('{DAV:}supported-privilege-set', $requestedProperties))) {

            unset($requestedProperties[$index]);
            $returnedProperties[200]['{DAV:}supported-privilege-set'] = new Sabre_DAVACL_Property_SupportedPrivilegeSet($this->getSupportedPrivilegeSet());

        }

    }

    /**
     * This method handels HTTP REPORT requests 
     * 
     * @param string $reportName 
     * @param DOMNode $dom 
     * @return void
     */
    public function report($reportName, $dom) {

        switch($reportName) {

            case '{DAV:}principal-property-search' :
                $this->principalPropertySearchReport($dom);
                return false;
            case '{DAV:}principal-search-property-set' :
                $this->principalSearchPropertySetReport($dom);
                return false; 
            case '{DAV:}expand-property' :
                $this->expandPropertyReport($dom);
                return false;

        }

    }

    /* }}} */

    /* Reports {{{ */

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

    /**
     * principalSearchPropertySetReport
     *
     * This method responsible for handing the 
     * {DAV:}principal-search-property-set report. This report returns a list
     * of properties the client may search on, using the
     * {DAV:}principal-property-search report.
     * 
     * @param DOMDocument $dom 
     * @return void
     */
    protected function principalSearchPropertySetReport(DOMDocument $dom) {

        $searchProperties = array(
            '{DAV:}displayname' => 'display name'
        );

        $httpDepth = $this->server->getHTTPDepth(0);
        if ($httpDepth!==0) {
            throw new Sabre_DAV_Exception_BadRequest('This report is only defined when Depth: 0');
        }
        
        if ($dom->firstChild->hasChildNodes()) 
            throw new Sabre_DAV_Exception_BadRequest('The principal-search-property-set report element is not allowed to have child elements'); 

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $root = $dom->createElement('d:principal-search-property-set');
        $dom->appendChild($root);
        // Adding in default namespaces
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $root->setAttribute('xmlns:' . $prefix,$namespace);

        }

        $nsList = $this->server->xmlNamespaces; 

        foreach($searchProperties as $propertyName=>$description) {

            $psp = $dom->createElement('d:principal-search-property');
            $root->appendChild($psp);

            $prop = $dom->createElement('d:prop');
            $psp->appendChild($prop);
  
            $propName = null;
            preg_match('/^{([^}]*)}(.*)$/',$propertyName,$propName);

            $currentProperty = $dom->createElement($nsList[$propName[1]] . ':' . $propName[2]);
            $prop->appendChild($currentProperty);

            $descriptionElem = $dom->createElement('d:description');
            $descriptionElem->setAttribute('xml:lang','en');
            $descriptionElem->appendChild($dom->createTextNode($description));
            $psp->appendChild($descriptionElem);


        }

        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody($dom->saveXML());

    }

    /**
     * principalPropertySearchReport
     *
     * This method is reponsible for handing the 
     * {DAV:}principal-property-search report. This report can be used for 
     * clients to search for groups of principals, based on the value of one
     * or more properties.
     * 
     * @param DOMDocument $dom 
     * @return void
     */
    protected function principalPropertySearchReport(DOMDocument $dom) {

        $searchableProperties = array(
            '{DAV:}displayname' => 'display name'

        );

        list($searchProperties, $requestedProperties, $applyToPrincipalCollectionSet) = $this->parsePrincipalPropertySearchReportRequest($dom);

        $result = array();

        if ($applyToPrincipalCollectionSet) {
            $uris = array();
        } else {
            $uris = array($this->server->getRequestUri());
        }

        $lookupResults = array();
        foreach($uris as $uri) {

            $p = array_keys($searchProperties);
            $p[] = '{DAV:}resourcetype';
            $r = $this->server->getPropertiesForPath($uri, $p, 1);

            // The first item in the results is the parent, so we get rid of it.
            array_shift($r);
            $lookupResults = array_merge($lookupResults, $r);
        } 

        $matches = array();

        foreach($lookupResults as $lookupResult) {

            // We're only looking for principals 
            if (!isset($lookupResult[200]['{DAV:}resourcetype']) || 
                (!($lookupResult[200]['{DAV:}resourcetype'] instanceof Sabre_DAV_Property_ResourceType)) ||
                !$lookupResult[200]['{DAV:}resourcetype']->is('{DAV:}principal')) continue;

            foreach($searchProperties as $searchProperty=>$searchValue) {
                if (!isset($searchableProperties[$searchProperty])) {
                    // If a property is not 'searchable', the spec dictates 
                    // this is not a match. 
                    continue;
                }

                if (isset($lookupResult[200][$searchProperty]) &&
                    mb_stripos($lookupResult[200][$searchProperty], $searchValue, 0, 'UTF-8')!==false) {
                        $matches[] = $lookupResult['href'];
                }

            }

        }

        $matchProperties = array();

        foreach($matches as $match) {
            
           list($result) = $this->server->getPropertiesForPath($match, $requestedProperties, 0);
           $matchProperties[] = $result;

        }

        $xml = $this->server->generateMultiStatus($matchProperties);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($xml);

    }

    /**
     * parsePrincipalPropertySearchReportRequest
     *
     * This method parses the request body from a
     * {DAV:}principal-property-search report.
     *
     * This method returns an array with two elements:
     *  1. an array with properties to search on, and their values
     *  2. a list of propertyvalues that should be returned for the request.
     * 
     * @param DOMDocument $dom 
     * @return array 
     */
    protected function parsePrincipalPropertySearchReportRequest($dom) {

        $httpDepth = $this->server->getHTTPDepth(0);
        if ($httpDepth!==0) {
            throw new Sabre_DAV_Exception_BadRequest('This report is only defined when Depth: 0');
        }

        $searchProperties = array();

        $applyToPrincipalCollectionSet = false;

        // Parsing the search request
        foreach($dom->firstChild->childNodes as $searchNode) {

            if (Sabre_DAV_XMLUtil::toClarkNotation($searchNode) == '{DAV:}apply-to-principal-collection-set')
                $applyToPrincipalCollectionSet = true;

            if (Sabre_DAV_XMLUtil::toClarkNotation($searchNode)!=='{DAV:}property-search')
                continue;

            $propertyName = null;
            $propertyValue = null;

            foreach($searchNode->childNodes as $childNode) {

                switch(Sabre_DAV_XMLUtil::toClarkNotation($childNode)) {

                    case '{DAV:}prop' :
                        $property = Sabre_DAV_XMLUtil::parseProperties($searchNode);
                        reset($property); 
                        $propertyName = key($property);
                        break;

                    case '{DAV:}match' :
                        $propertyValue = $childNode->textContent;
                        break;

                }


            }

            if (is_null($propertyName) || is_null($propertyValue))
                throw new Sabre_DAV_Exception_BadRequest('Invalid search request. propertyname: ' . $propertyName . '. propertvvalue: ' . $propertyValue);

            $searchProperties[$propertyName] = $propertyValue;

        }

        return array($searchProperties, array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild)), $applyToPrincipalCollectionSet);

    }


    /* }}} */

}
