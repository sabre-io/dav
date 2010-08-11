<?php

/**
 * Redirect-Reference plugin
 *
 * This plugin provides an implementation of RFC 4437.
 * This RFC describes a HTTP API for creation and managing redirects within a
 * WebDAV server.
 *
 * Using the MKREDIRECTREF and UPDATEREDIRECTREF HTTP methods you can create
 * and update redirects.
 *
 * TODO: currently PROPFIND on nodes containing redirect-references is not
 * yet handled.
 *
 * @package Sabre
 * @package DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Redirect_Plugin {

    /**
     * Temporary redirects
     */
    const TEMPORARY = 1;

    /**
     * Permanent redirects
     */
    const PERMANENT = 2;

    /**
     * Reference to main Server class 
     * 
     * @var Sabre_DAV_Server 
     */
    public $server;

    /**
     * Initializes the plugin 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $server->subscribeEvent('beforeMethod',array($this,'beforeMethod'));
        $server->subscribeEvent('unknownMethod',array($this,'unknownMethod'));
        $this->server = $server;

    }

    /**
     * This event is triggered before handling of any HTTP method.
     * 
     * @param string $method
     * @param string $uri
     * @return null|bool 
     */
    public function beforeMethod($method, $uri) {

        if (!$this->server->tree->nodeExists($uri))
            return;

        $node = $this->server->tree->getNodeForPath($uri);
        if (!($node instanceof Sabre_DAV_Redirect_IRedirectNode)) 
            return;

        $applyToRedirectRef = $this->httpRequest->getHeader('Apply-To-Redirect-Ref');
        if ($applyToRedirectRef!=='T') {

            $target = $node->getRedirectTarget();
            $this->server->httpResponse->sendStatus(302);
            $this->server->httpResponse->setHeader('Location',$location);
            $this->httpResponse->setHeader('Redirect-Ref',$location);
            return false;

        } else {
            // We need to operate on the redirect reference itself
            
            // The spec explicitly states to throw 403 when GET or 
            // PUT is called.
            if ($method==='PUT' || $method==='GET') {
                throw new Sabre_DAV_Exception_Forbidden('Cannot PUT or GET on redirect references');
            }

        } 

    }

    /**
     * This method returns a list of available HTTP methods
     * for a particular url.
     * 
     * @param string $uri 
     * @return array 
     */
    public function getMethods($uri) {

        list($parentUri) = Sabre_DAV_URLUtil::splitPath($uri);

        // If the node exists and it's a redirect-node we allow updating 
        if ($this->server->tree->nodeExists($uri)) {
            $node = $this->server->tree->getNodeForPath($uri);

            if ($node instanceof Sabre_DAV_Redirect_IRedirectNode) {
                return array('UPDATEREDIRECTREF');
            }

        // If the node does not exist and it's parent is a redirectparent we
        // allow creation
        } elseif ($this->server->tree->nodeExists($parentUri)) {

            $parentNode = $this->server->tree->getNodeForPath($parentUri);
            if ($parentNode instanceof Sabre_DAV_Redirect_IRedirectParent) {
                return array('MKREDIRECTREF');
            }
        }
        return array();

    }

    /**
     * Returns a list of features.
     *
     * This is used in the DAV: header in OPTIONS responses.
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('redirectrefs');

    }

    /**
     * This event is called for every method the server does not know how to
     * handle.
     *
     * This makes sure we interrupt MKREDIRECTREF and UPDATEREDIRECTREF.
     * 
     * @param string $method
     * @param string $uri
     * @return void
     */
    public function unknownMethod($method, $uri) {

        if ($method === 'MKREDIRECTREF') {
            $this->httpMkRedirectRef($uri);
            return false;
        }
        if ($method === 'UPDATEREDIRECTREF') {
            $this->httpUpdateRedirectRef($uri);
            return false;
        }

    }

    /**
     * Implementation of the MKREDIRECTREF HTTP Method 
     * 
     * @param string $uri 
     * @return void
     */
    public function httpMkRedirectRef($uri) {

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($this->httpRequest->getBody(true));
        $refTargetN = $dom->getElementsByTagNameNS('urn:DAV','href');
        $redirectLifeTimeN = $dom->getElementsByTagNameNS('urn:DAV','redirect-lifetime');

        if (!$refTargetN) throw new Sabre_DAV_Exception_BadRequest('The {DAV:}href element must be specified');
        $refTarget = $refTargetN->nodeValue;

        $redirectLifeTime = self::TEMPORARY;
        if ($redirectLifeTimeN) foreach($redirectLifeTimeN->children() as $child) {

            $n = Sabre_DAV_XMLUtil::toClarkNotation($child);
            if ($n==='{DAV:}temporary') {
                $redirectLifeTime = self::TEMPORARY;
                break;
            }
            if ($n==='{DAV:}permanent') {
                $redirectLifeTime = self::PERMANENT;
                break;
            }

        }
        
        list($parentUri, $newName) = Sabre_DAV_URLUtil::splitPath($uri);

        // validating the location of the node 
        if (!$this->server->tree->nodeExists($parent)) {
            throw new Sabre_DAV_Exception_Conflict('Parent node does not exist');
        }

        $parentNode = $this->server->tree->getNodeForPath($parent);
        if (!($parent instanceof Sabre_DAV_Redirect_IRedirectParent)) {
            throw new Sabre_DAV_Exception_MethodNotAllowed('The parent uri does not allow creation of references.');
        }

        if ($parent->childExists($newName)) {
            throw new Sabre_DAV_Exception_MethodNotAllowed('The resource you tried to create already exists');
        }

        // TODO validate reftarget
        $parent->createRedirect($newName, $redirectLifeTime, $refTarget);
        
        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:mkredirectref-response');
        $dom->appendChild($multiStatus);
        $xml = $dom->saveXML();
 
        $this->server->httpResponse->sendStatus(201);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($xml);

    }

    /**
     * Implementation of the UDPDATEREDIRECTREF HTTP Method 
     *
     * @param string $uri 
     * @return void
     */
    public function httpUpdateRedirectRef($uri) {

        $node = $this->server->tree->getNodeForPath($uri);
        if (!($node instanceof Sabre_DAV_Redirect_IRedirectNode)) {
            throw new Sabre_DAV_Exception_MethodNotAllowed('UPDATEREDIRECTREF is only allowed on IRedirectNode nodes');
        }

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($this->httpRequest->getBody(true));
        $refTargetN = $dom->getElementsByTagNameNS('urn:DAV','href');
        $redirectLifeTimeN = $dom->getElementsByTagNameNS('urn:DAV','redirect-lifetime');

        $refTarget = $refTargetN?$refTargetN->nodeValue:null;

        $redirectLifeTime = null;
        if ($redirectLifeTimeN) foreach($redirectLifeTimeN->children() as $child) {

            $n = Sabre_DAV_XMLUtil::toClarkNotation($child);
            if ($n==='{DAV:}temporary') {
                $redirectLifeTime = self::TEMPORARY;
                break;
            }
            if ($n==='{DAV:}permanent') {
                $redirectLifeTime = self::PERMANENT;
                break;
            }

        }

        $node->updateRedirect($refTarget, $redirectLifeTime);
        $this->server->httpResponse->sendStatus(200);

    }

}

?>
