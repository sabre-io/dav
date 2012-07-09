<?php

/**
 * This plugin implements support for caldav sharing.
 *
 * This spec is defined at:
 * http://svn.calendarserver.org/repository/calendarserver/CalendarServer/trunk/doc/Extensions/caldav-sharing-02.txt
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Sharing_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Reference to SabreDAV server object.
     *
     * @var Sabre_DAV_Server
     */
    protected $server;

    /**
     * This method should return a list of server-features.
     *
     * This is for example 'versioning' and is added to the DAV: header
     * in an OPTIONS response.
     *
     * @return array
     */
    public function getFeatures() {

        return array('calendarserver-sharing');

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

        return 'calendarserver-sharing';

    }

    /**
     * This initializes the plugin.
     *
     * This function is called by Sabre_DAV_Server, after
     * addPlugin is called.
     *
     * This method should set up the required event subscriptions.
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->resourceTypeMapping['Sabre_CalDAV_IShareableCalendar'] = '{' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '}shared-owner';
        $server->resourceTypeMapping['Sabre_CalDAV_ISharedCalendar'] = '{' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '}shared';

        $this->server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));
        $this->server->subscribeEvent('unknownMethod', array($this,'unknownMethod'));

    }

    /**
     * This event is triggered when properties are requested for a certain 
     * node.
     *
     * This allows us to inject any properties early. 
     * 
     * @param string $path 
     * @param Sabre_DAV_INode $node 
     * @param array $requestedProperties 
     * @param array $returnedProperties 
     * @return void
     */
    public function beforeGetProperties($path, Sabre_DAV_INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof Sabre_CalDAV_IShareableCalendar) {
            if (($index = array_search('{' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '}invite', $requestedProperties))!==false) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '}invite'] = 
                    new Sabre_CalDAV_Property_Invite(
                        $node->getShares()
                    );

            }

    }

    /**
     * This event is triggered when the server didn't know how to handle a
     * certain request.
     *
     * We intercept this to handle POST requests on calendars.
     *
     * @param string $method
     * @param string $uri
     * @return null|bool 
     */
    public function unknownMethod($method, $uri) {

        if ($method!=='POST') {
            return;
        }

        // Only handling application/xml
        $contentType = $this->server->httpRequest->getHeader('Content-Type');
        if (strpos($contentType,'application/xml')===false)
            return;

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($this->server->httpRequest->getBody());

        // We're only handling 'share' documents
        if (Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild) !== '{http://calendarserver.org/ns/}share') {
            return;
        }

        // Making sure the node exists
        try {
            $node = $this->server->tree->getNodeForPath($uri);
        } catch (Sabre_DAV_Exception_NotFound $e) {
            return;
        }

        // We can only deal with IShareableCalendar objects
        if (!$node instanceof Sabre_CalDAV_IShareableCalendar) {
            return;
        }

        // Getting ACL info
        $acl = $this->server->getPlugin('acl');

        // If there's no ACL support, we allow everything
        if ($acl) {
            $acl->checkPrivileges($uri, '{DAV:}write');

            // Also, you can only make changes if you're the owner of the 
            // calendar (and not a sharee)
            $principals = $acl->getCurrentUserPrincipals();
            if (!in_array($node->getOwner(), $principals)) {
                throw new Sabre_DAV_Exception_Forbidden('Only the owner of the calendar can make changes to the list of sharees');
            }  

        }

        $mutations = $this->parseShareRequest($dom);

        $node->updateShares($mutations[0], $mutations[1]);

        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody('Updates shares');

        // Breaking the event chain
        return false;

    }

    /**
     * Parses the 'share' POST request.
     *
     * This method returns an array, containing two arrays.
     * The first array is a list of new sharees. Every element is a struct 
     * containing a:
     *   * href element. (usually a mailto: address)
     *   * commonName element (often a first and lastname, but can also be
     *     false)
     *   * readOnly (true or false)
     *   * summary (A description of the share, can also be false)
     *
     * The second array is a list of sharees that are to be removed. This is
     * just a simple array with 'hrefs'.
     * 
     * @param DOMDocument $dom 
     * @return array 
     */
    protected function parseShareRequest(DOMDocument $dom) {

        $xpath = \DOMXPath($dom);
        $xpath->registerNamespace('cs', Sabre_CalDAV_Plugin::NS_CALENDARSERVER);
        $xpath->registerNamespace('d', 'urn:DAV');


        $set = array();
        $elems = $xpath->query('cs:set');

        for($i=0; $i < $elems->length; $i++) {

            $xset = $elems->item($i);
            $set[] = array(
                'href' => $xpath->evaluate('string(d:href)', $xset),
                'commonName' => $xpath->evaluate('string(cs:common-name)', $xset),
                'summary' => $xpath->evaluate('string(cs:summary)', $xset),
                'readOnly' => $xpath->evaluate('cs:read', $xset)!==false
            );

        }

        $remove = array();
        $elems = $xpath->query('cs:remove');

        for($i=0; $i < $elems->length; $i++) {

            $xremove = $elems->item($i);
            $remove[] = $xpath->evaluate('string(d:href)', $xremove);

        }

        return array($set, $remove);

    }

}
