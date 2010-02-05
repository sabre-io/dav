<?php

/**
 * WebDAV ACL Plugin
 * 
 * This plugin adds access-control functionality to your WebDAV server.
 * The plugin implements RFC 3744  
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Plugin extends Sabre_DAV_ServerPlugin {

    const RECURSIVE = 1;

    const PRINCIPAL_ROOT = 'principals';

    /**
     * ACL backend.
     * The backend class stores all ACL information.
     * 
     * @var Sabre_DAVACL_Backend_Abstract 
     */
    protected $backend;

    /**
     * Server object 
     * 
     * @var Sabre_DAV_Server 
     */
    protected $server;

    /**
     * Constructor.
     *
     * Your ACL backend object must be passed 
     * 
     * @param Sabre_DAVACL_Backend_Abstract $backend 
     */
    public function __construct(Sabre_DAVACL_Backend_Abstract $backend) {

        $this->backend = $backend;

    }

    /**
     * The getFeatures method must return any feature that is supposed to show
     * up in the 'Dav:' header from the OPTIONS method.
     *
     * According to RFC 3744 'access-control' should show up here.
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('access-control');

    }

    /**
     * Returns a list of newly available HTTP methods.
     *
     * This is just used to populate the HTTP Allow: header from OPTIONS.
     * 
     * @return array 
     */
    public function getMethods() {

        return array('ACL');

    }

    /**
     * Returns the currently logged in principal uri.
     *
     * If nobody is currently logged in, this method will throw an exception 
     *
     * @return string 
     */
    public function getCurrentPrincipalUri() {

        $authPlugin = $this->server->getPlugin('Sabre_DAV_Auth_Plugin');
        if (!$authPlugin) throw new Sabre_DAV_Exception('The Sabre_DAV_Auth_Plugin was not loaded');
        
        $userInfo = $authPlugin->getUserInfo();

        if (!$userInfo) throw new Sabre_DAV_Exception('The current principal uri was requested, but nobody is currently logged in'); 

        return self::PRINCIPAL_ROOT . '/' .$userInfo['userId'];
    
    } 

    /**
     * Initializes the plugin.
     *
     * This is automatically called by the Server class. It will register
     * various events.
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('afterGetProperties',array($this,'afterGetProperties'));
        $server->subscribeEvent('unknownMethod',array($this,'unknownMethod'));
        $server->subscribeEvent('beforeMethod',array($this,'beforeMethod'),20);
        $server->subscribeEvent('beforeBind',array($this,'beforeBind'));
        $server->subscribeEvent('beforeUnbind',array($this,'beforeUnbind'));
        $server->subscribeEvent('beforeLock',array($this,'beforeLock'));
        $server->subscribeEvent('beforeUnlock',array($this,'beforeUnlock'));
        $server->subscribeEvent('beforeWriteContent',array($this,'beforeWriteContent'));
        $server->subscribeEvent('afterBind',array($this,'afterBind'));
        $server->subscribeEvent('report',array($this,'report'));

    }

    /**
     * afterGetoperties event.
     *
     * This method is called after all properties have been retrieved for one
     * resource. It allows us to add in any 404'd properties which are actually
     * defined by this plugin, as well as make sure users' cannot read properties
     * they are not allowed to.
     * 
     * @param string $path 
     * @param array $properties 
     * @return bool 
     */
    public function afterGetProperties($path,&$properties) {

        $authPlugin = $this->server->getPlugin('Sabre_DAV_Auth_Plugin');
        if (!$authPlugin) throw new Sabre_DAV_Exception('The Sabre_DAV_Auth_Plugin was not loaded');

        // TODO: we currently fold current-user-privilege-set and read-acl in to read
        $failedPrivileges = array();
        if (!$this->backend->checkPrivilege($path,$this->getCurrentPrincipalUri(),array('{DAV:}read'),$failedPrivileges)) {

            if (!isset($properties[403])) $properties[403] = array();
            foreach($properties[200] as $propertyName=>$propValue) {
                // TODO: only exception is resourcetype. Other systems depend on this. Will be fixed later
                if ($propertyName=='{DAV:}resourcetype') continue;

                $properties[403][$propertyName] = null;
                unset($properties[200][$propertyName]);
            }
            return true;

        }

        $node = null;

        foreach($properties[404] as $rProperty=>$discard) {

            switch($rProperty) {

                case '{DAV:}owner' :
                    if(!$node) $node = $this->server->tree->getNodeForPath($path); 
                    if ($node instanceof Sabre_DAVACL_IACLNode) {
                        $properties[200][$rProperty] = new Sabre_DAV_Property_Href($node->getOwner());
                    } else {
                        $properties[200][$rProperty] = '';
                    }
                    unset($properties[404][$rProperty]);
                    break;

                case '{DAV:}group' :
                    // according to rfc3744#5.1 and #5.2 we can return these
                    // as empty properties
                    $properties[200][$rProperty] = '';
                    unset($properties[404][$rProperty]);
                    break;

                case '{DAV:}supported-privilege-set' :
                    $properties[200][$rProperty] = new Sabre_DAVACL_Property_SupportedPrivilegeSet($this->backend->getSupportedPrivileges());
                    unset($properties[404][$rProperty]);
                    break;

                case '{DAV:}current-user-privilege-set' :
                    $properties[200][$rProperty] = new Sabre_DAVACL_Property_CurrentUserPrivilegeSet($this->backend->getPrivilegesForPrincipal($path,$this->getCurrentPrincipalUri()));
                    unset($properties[404][$rProperty]);
                    break;

                case '{DAV:}acl-restrictions' :
                    $properties[200][$rProperty] = new Sabre_DAVACL_Property_AclRestrictions();
                    unset($properties[404][$rProperty]);
                    break;

                case '{DAV:}acl' :
                    $acl = $this->backend->getACL($path);
                    $properties[200][$rProperty] = new Sabre_DAVACL_Property_Acl($acl);
                    unset($properties[404][$rProperty]);
                    break;

                case '{DAV:}inherited-acl-set' :
                    $properties[200][$rProperty] = null;
                    unset($properties[404][$rProperty]);
                    break;

                case '{DAV:}principal-collection-set' :
                    $properties[200][$rProperty] = new Sabre_DAV_Property_Href(self::PRINCIPAL_ROOT . '/');
                    unset($properties[404][$rProperty]);
                    break;
            }

        }
        if (array_key_exists('{DAV:}supported-report-set', $properties[200])) {
            $properties[200]['{DAV:}supported-report-set']->addReport(array(
                '{DAV:}acl-principal-prop-set',
                '{DAV:}expand-property',
                '{DAV:}principal-match',
                '{DAV:}principal-property-search',
                '{DAV:}principal-search-property-set',
            ));
        }

        return true;

    }

    /**
     * unknownMethod event handler
     *
     * This method is responsible for intercepting the ACL method.
     * 
     * @param string $method 
     * @return void
     */
    public function unknownMethod($method) {

        if ($method!='ACL') return; 
        
        $this->httpACL();
        return false;

    }

    /**
     * HTTP ACL method handler.
     *
     * This method handles the ACL method, as defined in RFC 3744
     * The ACL method is used to update a resource's access control entries.
     */
    public function httpACL() { 

        // Checking permission
        $uri = $this->server->getRequestUri();
        $this->checkPrivilege($uri,array('{DAV:}write-acl'));

        // We need to make sure the resource is not locked
        $locksPlugin = $this->server->getPlugin('Sabre_DAV_Locks_Plugin');
        if ($locksPlugin) {
            $lastLock = null;
            if (!$locksPlugin->validateLock(null,$lastLock)) {
                throw new Sabre_DAV_Exception_Locked($lastLock);
            }
        }

        $body = $this->server->httpRequest->getBody(true);
        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body); 
    
        /* NOTE: this debug code disables the ACL method *
        $this->server->httpResponse->setHeader('Content-Length','0');
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody('');
        return false;
        */

        $aces = array();

        foreach($dom->getElementsByTagNameNS('urn:DAV','ace') as $xAce) {

            $currentAce = array(
                'principal' => null,
                'grant' => array(),
                'special' => false // special is true for all, unauthenticatd, authenticated, etc..
            );

            foreach($xAce->childNodes as $childNode) {

                // Make sure we discard anything but normal XML nodes
                if ($childNode->nodeType != XML_ELEMENT_NODE) continue;

                $fullTag = Sabre_DAV_XMLUtil::toClarkNotation($childNode);

                switch($fullTag) {

                    case '{DAV:}principal' :
                        switch(Sabre_DAV_XMLUtil::toClarkNotation($childNode->firstChild)) {
                            case '{DAV:}href' :
                                $currentAce['principal'] = $childNode->firstChild->nodeValue;
                                break;
                            case '{DAV:}authenticated' :
                                $currentAce['principal'] = '{DAV:}authenticated';
                                $currentAce['special'] = true;
                                break;
                            case '{DAV:}property' :
                            case '{DAV:}all' :
                            case '{DAV:}unauthenticated' :
                            case '{DAV:}self' :
                            default :
                                // We currently don't support any of these
                                throw new Sabre_DAVACL_Exception_AllowedPrincipal();
                                break;

                        }
                        break;

                    case '{DAV:}invert' :
                        throw new Sabre_DAVACL_Exception_NoInvert();

                    case '{DAV:}grant' :
                        foreach($childNode->childNodes as $priv) {
                            $currentAce['grant'][] = Sabre_DAV_XMLUtil::toClarkNotation($priv->firstChild);
                        }
                        break;

                    case '{DAV:}deny' :
                        throw new Sabre_DAVACL_Exception_GrantOnly();


                }

            }
            $aces[] = $currentAce;

        }

        // New aces have been gathered, now we need to do some additional
        // validation
        foreach($aces as $ace) {

            if (is_null($ace['principal'])) {
                throw new Sabre_DAV_Exception_BadRequest('No principal was given for ace');
            }
            
            if (!$ace['special']) { 

                $ace['principal'] = $this->server->calculateUri($ace['principal']);

                // Making sure this ace is within the principal base path
                if (strpos($ace['principal'],self::PRINCIPAL_ROOT . '/')!==0)
                    throw new Sabre_DAV_Exception_RecognizedPrincipal($ace['principal']);

                // Making sure principal exists
                try { 
                    $principalNode = $this->server->getNodeForPath($ace['principal']);
                } catch (Sabre_DAV_FileNotFound $e) {
                    throw new Sabre_DAV_Exception_RecognizedPrincipal($ace['principal']);
                }

            }

            // Validating all new privileges
            foreach($ace['grant'] as $key=>$privilege) {

                $privInfo = $this->backend->getPrivilegeInfo($privilege);
                if (!$privInfo)
                    throw new Sabre_DAVACL_Exception_NotSupportedPrivilege($privilege);

                if ($privInfo['abstract'])
                    throw new Sabre_DAVACL_Exception_NoAbstract($privilege);

            }

        }
        
        $this->backend->setACL($this->server->getRequestUri(),$aces);
        $this->server->httpResponse->setHeader('Content-Length','0');
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody('');

        return false;

    }

    /**
     * beforeMethod event. 
     *
     * This event is called right before method-specific code is executed.
     * The ACL plugin uses this to intercept some access.
     * 
     * @param string $method 
     * @return bool 
     */
    public function beforeMethod($method) {
        
        $checkPermissions = array();
        $uri = $this->server->getRequestUri();

        $options = 0;

        switch($method) {

            case 'COPY' :
                $checkPermissions[] = '{DAV:}read';
                $options = $options | self::RECURSIVE;
                break;
            case 'GET' :
            case 'HEAD' :
            case 'OPTIONS' :
                // We only check if the resource does not exist
                $node = $this->server->tree->getNodeForPath($uri); 
                $checkPermissions[] = '{DAV:}read';
                break;
            case 'PROPPATCH' :
                $checkPermissions[] = '{DAV:}write-properties';
                break;
            case 'ACL' :
                $checkPermissions[] = '{DAV:}write-acl';
                break;

        }
        $this->checkPrivilege($uri,$checkPermissions,$options);
        return true;

    }

    /**
     * beforeBind event.
     *
     * This event is called by the server right before any new resource is
     * created.
     * 
     * @param string $uri 
     * @return bool 
     */
    public function beforeBind($uri) {

        // Note that we need to check the uri of the parent, not the 
        // actual uri being created.

        $parentUri = dirname($uri);
        if ($parentUri=='.') $parentUri ='';
        $this->checkPrivilege($parentUri,array('{DAV:}bind'));
        return true;

    }

    /**
     * beforeUnbind event.
     *
     * This event is triggered right before any resource is about to be 
     * deleted.
     * 
     * @param string $uri 
     * @return void
     */
    public function beforeUnbind($uri) {

        // Note that we need to check the uri of the parent, not the 
        // actual uri being deleted.
        $parentUri = dirname($uri);
        if ($parentUri=='.') $parentUri ='';

        $this->checkPrivilege($uri,array('{DAV:}unbind'));
        return true;

    }

    /**
     * beforeWriteContent event.
     *
     * This event is triggered right before any existing resources are about
     * to be overwritten with new content. PUT on an existing resource would 
     * trigger this.
     * 
     * @param string $uri 
     * @return bool 
     */
    public function beforeWriteContent($uri) {

        $this->checkPrivilege($uri,array('{DAV:}write-content'));
        return true;

    }

    /**
     * beforeUnlock event handler
     *
     * This event intercepts unlock, and only allows users who set a particular lock,
     * or users with the unlock privilege to unlock.
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lock 
     * @return bool 
     */
    public function beforeUnlock($uri,Sabre_DAV_Locks_LockInfo $lock) {
       
        $authPlugin = $this->server->getPlugin('Sabre_DAV_Auth_Plugin');
        if (!$authPlugin) throw new Sabre_DAV_Exception('The Sabre_DAV_Auth_Plugin was not loaded');
        $userId = $authPlugin->getUserId();

        if ($lock->owner != $userId) {
            $this->checkPrivilege($uri,array('{DAV:}unlock'));
        }
        return true;

    }

    /**
     * beforeLock event handler
     *
     * This event handler overwrites the lock's owner information with a user id
     * this is used to determine who set a particular lock, and allows us to prevent
     * other users from unlocking.
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lock 
     * @return bool 
     */
    public function beforeLock($uri,Sabre_DAV_Locks_LockInfo $lock) {

        // We are overriding the supplied information for owner
        // and making sure it has a real user id.
        $authPlugin = $this->server->getPlugin('Sabre_DAV_Auth_Plugin');
        if (!$authPlugin) throw new Sabre_DAV_Exception('The Sabre_DAV_Auth_Plugin was not loaded');
        $userId = $authPlugin->getUserId();
        $lock->owner = $userId;
        return true;

    }

    /**
     * afterBind event handler
     *
     * This event is triggered right after a new resource is created.
     * This plugin uses this event to setup the initial privileges.
     * 
     * @param string $uri 
     * @return bool 
     */
    public function afterBind($uri) {

        $this->backend->setInitialPrivileges($uri,$this->getCurrentPrincipalUri());
        return true;

    }

    /**
     * Checks if a user has a particular privilege and throw an exception if this is not the case
     *
     * This is mostly a convenience wrapper.
     * 
     * @param string $uri 
     * @param array $permissions 
     * @param int $options 
     * @return void
     */
    public function checkPrivilege($uri,array $permissions,$options = 0) {

        $failedPrivileges = array();
        if (!$this->backend->checkPrivilege($uri,$this->getCurrentPrincipalUri(),$permissions,$failedPrivileges,$options)) {
            throw new Sabre_DAVACL_Exception_NeedPrivileges($uri,$failedPrivileges);
        }

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
            case '{DAV:}acl-principal-prop-set' :
                return $this->aclPrincipalPropSetReport($dom);
            case '{DAV:}expand-property' :
                return $this->expandPropertyReport($dom);
            case '{DAV:}principal-match' :
                return $this->principalMatchReport($dom);
            case '{DAV:}principal-property-search' :
                return $this->principalPropertySearchReport($dom);
            case '{DAV:}principal-search-property-set' :
                return $this->principalSearchPropertySetReport($dom);
            default :
                return true;

        }
    
    }
    
    
    /**
     * acl-principal-prop-set report
     *
     * This report is defined by RFC 3744 Section 9.2. It allows a client to request
     * properties from principals that are part of a resource's ACL. 
     * 
     * @param DomNode $dom 
     * @return void
     */
    protected function aclPrincipalPropSetReport($dom) {
    
        // TODO: needs permission check

        $depth = $this->server->getHTTPDepth(0);
        // the specification says if depth is anything besides 0, we most throw back 400
        if ($depth!=0) throw new Sabre_DAV_Exception_BadRequest('Only a Depth of 0 is supported for this report');

        // Properties requested for each principal
        $requestedProperties = $this->server->parseProps($dom);
        
        $requestUri = $this->server->getRequestUri();
        
        $acl = $this->backend->getACL($requestUri);

        $principals = array();

        foreach($acl as $ace) {
            if ($ace['special']) continue;
            $principals[] = $ace['principal'];
        }

        array_unique($principals);

        $properties = array();
        foreach($principals as $principal) {

            $principalProps = $this->server->getPropertiesForPath($principal,$requestedProperties);
            $properties[] = $principalProps[0];

        }

        $multiStatus = $this->server->generateMultiStatus($properties);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($multiStatus);
   
        // Make sure the event chain is broken
        return false;

    }

    /**
     * expandPropertyReport 
     * 
     * @param DomNode $dom 
     * @return void
     */
    protected function expandPropertyReport($dom) {

        $requestedProperties = $this->parseExpandPropertRequest($dom);
        $depth = $this->server->getHTTPDepth(0);
        $requestUri = $this->server->getRequestUri();

        $resources = $this->server->getPropertiesForPath($requestUri,array_keys($requestedProperties),$depth);

        foreach($resources as &$resource) {

            $this->expandProperties($resource,$requestedProperties);

        }

        $multiStatus = $this->server->generateMultiStatus($resources);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($multiStatus);

        // Make sure the event chain is broken
        return false;

    }

    protected function parseExpandPropertyReportRequest($node) {

        $requestedProperties = array();
        do {

            if ($node->namespaceURI != 'urn:DAV' || $node->localName != 'property') continue; 
                
            if ($node->firstChild) {
                
                $children = $this->parseExpandPropertyReportProperties($node->firstChild);

            } else {

                $children = array();

            }

            $namespace = $node->getAttribute('namespace');
            if (!$namespace) $namespace = '{DAV:}';

            $propName = '{'.$namespace.'}' . $node->getAttribute('name');
            $requestedProperties[$propName] = $children; 

        } while ($node = $node->nextSibling);

    }

    protected function expandProperties(array &$resource,array $requestedProperties) { 

        foreach($requestedProperties as $propName=>$children) {

            if (count($childProperties)<1) break;

            // But only if this was a property with href value
            if (!isset($resource[200][$propertyName])) break;
            if (!($resource[200][$propertyName] instanceof Sabre_DAV_Property_Href)) break;
            
            $href = $resource[200][$propertyName]->getHref();

            list($childResource) = $this->server->getPropertiesForPath($href,array_keys($children));
            
            // Child elementsa are also expanded again
            $this->expandProperties($childResource,$children);

            // Finally, this is all wrapped in a Request property
            $resource[200][$propertyName] = new Sabre_DAV_Property_Response($href,$childResource);

        }

    }

    /**
     * The principalmatch report is used to find resources related to the principal
     *
     * This can be either 'self', which should return the actual principal urls and any 
     * groups they might belong to, or a property such as {DAV:}owner. In this case 
     * we need to check if the property matches the principal url.
     * 
     * @param DOMNode $dom 
     * @return void 
     */
    protected function principalMatchReport($dom) {

        $depth = $this->server->getHTTPDepth(0);
        // the specification says if depth is anything besides 0, we most throw back 400
        if ($depth!=0) throw new Sabre_DAV_Exception_BadRequest('Only a Depth of 0 is supported for this report');

        $requestedProperties = $this->server->parseProps($dom);
        $principalUri = $this->getCurrentPrincipalUri();

        $result = null;
        foreach($dom->childNodes as $childNode) {

            // Make sure we discard anything but normal XML nodes
            if ($childNode->propNodeData->nodeType != XML_ELEMENT_NODE) continue;

            $fullTag = Sabre_DAVACL_XML::toClarkNotation($childNode);

            switch($fullTag) {

                case '{DAV:}self' :
                    // Client is asking for uri's identifiying the principal
                    // and the groups it belongs to. Since there is no group
                    // support yet, this is easy.
                    $result = $this->server->getPropertiesForPath($principalUri,$requestedProperties);
                    break;
                case '{DAV:}principal-property' :
                    // The client is asking us to traverse the entire tree, and find any property
                    // that matches the principal uri. This is heavy, but required.
                    $subProperty = $childNode->firstChild;
                    $subPropertyNS = $subProperty->namespaceURI;
                    if ($subPropertyNS=='urn:DAV') $subPropertyNS = 'DAV:';
                    $propertyName = '{' . $subPropertyNS . '}' . $property->localName;
                    $result = $this->principalMatchReportPropertySearch($principalUri,$this->server->getRequestUri(),$propertyName,$requestedProperties);
                    break;

            }
            

        }

        if (is_null($result)) throw new Sabre_DAV_Exception_BadRequest('Either the principal-property or self element must be supplied');
        $multiStatus = $this->server->generateMultiStatus($result);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($multiStatus);

        // Make sure the event chain is broken
        return false;

    }

    /**
     * principalMatchReportPropertySearch
     *
     * This method is used by principalMatchReport.
     * It searches an entire tree for the property with the given name (searchProperty)
     * to match the currently logged in principal uri. 
     * 
     * @param string $principalUri The principaluri to search for 
     * @param string $searchUri The uri to search in 
     * @param string $searchProperty The property to match for  
     * @param array $requestedProperties The properties to return 
     * @return array 
     */
    protected function principalMatchReportPropertySearch($principalUri,$searchUri,$searchProperty,$requestedProperties) {

        $results = array();

        // Children first
        $currentNode = $this->server->tree->getNodeForPath($searchUri);
        if ($currentNode instanceof Sabre_DAV_IDirectory) {
            foreach($currentNode->getChildren() as $child) {
                $results = array_merge($results,$this->principalMatchReportPropertySearch($principalUri,$searchUri.'/' . $child->getName(),$searchProperty,$requestedProperties));
            }
        }
        
        $matchProp = $this->server->getPropertiesForPath($searchUri,array($searchProperty));
        if (!isset($matchProp[200][$searchProperty])) return $results;
        if (!($matchProp[200][$searchProperty] instanceof Sabre_DAV_Property_Href)) return $results;

        if ($matchProp[200][$searchProperty]->getHref()!=$principalUri) return $results;

        // We got a match, fetching additinal properties
        $results = array_merge($results,
            $this->server->getPropertiesForPath($searchUri,$requestedProperties)
        );
        
        return $results;

    }

    protected function principalPropertySearchReport($dom) {

        $depth = $this->server->getHTTPDepth(0);
        // the specification says if depth is anything besides 0, we most throw back 400
        if ($depth!=0) throw new Sabre_DAV_Exception_BadRequest('Only a Depth of 0 is supported for this report');
       
        $requestUri = $this->server->getRequestUri();
        $requestedProperties = $this->server->parseProps($dom);

        $searchProperties = array();

        foreach($dom->childNodes as $childNode) {

            // Make sure we discard anything but normal XML nodes
            if ($childNode->propNodeData->nodeType != XML_ELEMENT_NODE) continue;

            $fullTag = Sabre_DAVACL_XML::toClarkNotation($childNode);

            switch($fullTag) {

                case '{DAV:}property-search' :
                    $searchProp = $childNode->getElementsByTagNameNS('urn:DAV','prop');
                    if (!count($propName)!==1) throw new Sabre_DAV_Exception_BadRequest('Invalid REPORT request body');
                    $searchProp = $propName[0]->firstChild;
                    $searchPropName = Sabre_DAVACL_XML::toClarkNotation($searchProp);
                    $match = $childNode->getElementsByTagNameNS('urn:DAV','match');
                    if (!count($match)!==1) throw new Sabre_DAV_Exception_BadRequest('Invalid REPORT request body');
                    $searchProperties[$searchPropName]==$match->nodeValue;
                    break;

                case '{DAV:}apply-to-principal-collection-set' :
                    // If this element was set, we need to ignore the request uri, and use the
                    // principal uri instead.
                    $requestUri = self::PRINCIPAL_ROOT;
                    break;

            }
            

        }

        // If the report request uri didn't match the principal collection
        // we can pretend this report doesn't exist
        if ($requestUri!==self::PRINCIPAL_ROOT) return true;
        
        $result = array();

        // We don't support searching for anything but {DAV:}displayname
        if (isset($searchProperties['{DAV:}displayname'])) {
            $displayName = $searchProperties['{DAV:}displayname'];
            unset($searchProperties['{DAV:}displayname']);
        }

        // If the client requested other properties to search on (>0), then
        // we shouldn't return anything
        if (count($searchProperties)==0) {
            $possibleMatches = $this->server->getPropertiesForPath($requestUri,array('{DAV:}displayname'),1);
            foreach($possibleMatches as $match) {
                if (isset($possibleMatches[200]['{DAV:}displayname'])) {
                    if (mb_strpos(mb_strtolower($match),mb_strtolower($possibleMatches[200]['{DAV:}displayname']))===true)
                        $resultUris[] = $possibleMatches['href'];
                }
            }

            foreach($resultUris as $resultUri) {

                $result = array_merge($result,$this->server->getPropertiesForPath($resultUri,$requestedProperties));

            }

        }

        $multiStatus = $this->server->generateMultiStatus($result);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($multiStatus);

        // Make sure the event chain is broken
        return false;
    }

    /**
     * This report allows a user to retrieve all the propertynames a
     * client can search on, using the principal-property-search report.
     *
     * This report only has to be defined for principal collections.
     * 
     * @param DOMNode $dom 
     * @return void
     */
    protected function principalSearchPropertySetReport($dom) {

        $depth = $this->server->getHTTPDepth(0);
        // the specification says if depth is anything besides 0, we most throw back 400
        if ($depth!=0) throw new Sabre_DAV_Exception_BadRequest('Only a Depth of 0 is supported for this report');
       
        // For non-principal-collections, this report is not defined
        if ($this->server->getRequestUri()!=self::PRINCIPAL_ROOT) return true;

        // We will only support searching for displayName
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody(
'<?xml version="1.0" encoding="utf-8" ?>
<D:principal-search-property-set xmlns:D="DAV:">
  <D:principal-search-property>
    <D:prop>
       <D:displayname/>
    </D:prop>
    <D:description xml:lang="en">Full name</D:description>
  </D:principal-search-property>
</D:principal-search-property-set>');

        // Make sure the event chain is broken
        return false;

    }



}
