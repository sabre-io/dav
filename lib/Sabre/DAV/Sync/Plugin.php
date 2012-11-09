<?php

namespace Sabre\DAV\Sync;

use Sabre\DAV;

/**
 * This plugin all WebDAV-sync capabilities to the Server.
 *
 * The sync capabilities only work with collections that implement
 * Sabreu\DAV\Sync\ISyncCollection.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Plugin extends DAV\ServerPlugin {

    /**
     * Reference to server object
     *
     * @var DAV\Server
     */
    protected $server;

    /**
     * Initializes the plugin.
     *
     * This is when the plugin registers it's hooks.
     *
     * @param DAV\Server $server
     * @return void
     */
    public function initialize(DAV\Server $server) {

        $this->server = $server;

        $self = $this;

        $server->subscribeEvent('report', function($reportName, $dom, $uri) use ($self) {

            if ($reportName === '{DAV:}sync-collection') {
                $self->syncCollection($uri, $dom);
                return false;
            }

        });

        $server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));

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

        $node = $this->tree->getNodeForPath($uri);
        if ($node instanceof ICollection && $node->getSyncToken()) {
            return array(
                '{DAV:}sync-collection',
            );
        }

        return array();

    }


    /**
     * This method handles the {DAV:}sync-collection HTTP REPORT.
     *
     * @param string $uri
     * @param DOMDocument $dom
     * @return void
     */
    public function syncCollection($uri, DOMDocument $dom) {

        // rfc3253 specifies 0 is the default value for Depth:
        $depth = $this->server->getHTTPDepth(0);

        list(
            $syncToken,
            $syncLevel,
            $limit,
            $properties
        ) = $this->parseSyncCollectionRequest($dom, $depth);

        // Getting the data
        $node = $this->server->tree->getNodeForPath($uri);
        if (!$node instanceof ISyncCollection) {
            throw new DAV\Exception\ReportNotImplemented('The {DAV:}sync-collection REPORT is not implemented on this url.');
        }
        $token = $node->getSyncToken();
        if (!$token) {
            throw new DAV\Exception\ReportNotImplemented('No sync information is available at this node');
        }

        $changeInfo = $node->getChanges($syncToken, $syncLevel, $limit);

        // Encoding the response
        $this->sendSyncResponse(
            $changeInfo['syncToken'],
            $uri,
            $changeInfo['modified'],
            $changeInfo['deleted'],
            $properties
        );

    }

    /**
     * Parses the {DAV:}sync-collection REPORT request body.
     *
     * This method returns an array with 3 values:
     *   0 - the value of the {DAV:}sync-token element
     *   1 - the value of the {DAV:}sync-level element
     *   2 - The value of the {DAV:}limit element
     *   3 - A list of requested properties
     *
     * @param DOMDocument $dom
     * @param int $depth
     * @return void
     */
    protected function parseSyncCollectionRequest(DOMDocument $dom, $depth) {

        $xpath = new DOMXPath($dom);
        $xpath->registerXPathNamespace('d','DAV:');

        $syncToken = $xpath->query("//d:sync-token");
        if ($syncToken->length !== 1) {
            throw new DAV\Exception\BadRequest('You must specify a {DAV:}sync-token element, and it must appear exactly once');
        }
        $syncToken = $syncToken->item(0)->nodeValue;

        $syncLevel = $xpath->query("//d:sync-level");
        if ($syncLevel->length === 0) {
            // In case there was no sync-level, it could mean that we're dealing
            // with an old client. For these we must use the depth header
            // instead.
            $syncLevel = $depth;
        } else {
            $syncLevel = $syncLevel->item(0)->nodeValue;
            if ($syncLevel === 'infinite') {
                $syncLevel = DAV\Server::DEPTH_INFINITE;
            }

            // If the syncLevel was set, and depth is something other than 0..
            // we must fail.
            if ($depth != 0) {
                throw new DAV\Exception\Forbidden('The sync-collection report is only implemented on depth: 0');
            }

        }
        $limit = $xpath->query("//d:limit/d:nresults");
        if ($limit->length === 0) {
            $limit = null;
        } else {
            $limit = $limit->item(0)->nodeValue;
        }

        $properties = DAV\XMLUtil::getProperties($dom);

        return array(
            $syncToken,
            $syncLevel,
            $limit,
            $properties,
        );

    }

    /**
     * Sends the response to a sync-collection request.
     *
     * @param string $syncToken
     * @param string $collectionUrl
     * @param array $modified
     * @param array $deleted
     * @param array $properties
     * @return void
     */
    protected function sendSyncCollectionResponse($syncToken, $collectionUrl, array $modified, array $deleted, array $properties) {

        $dom = new DOMDocument('1.0','utf-8');
        //$dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');
        $dom->appendChild($multiStatus);

        // Adding in default namespaces
        foreach($this->xmlNamespaces as $namespace=>$prefix) {

            $multiStatus->setAttribute('xmlns:' . $prefix,$namespace);

        }

        foreach($modified as $item) {
            $fullPath = $collectionUrl . $item;

            // We must still fetch the requested properties from the server
            // class.
            $propertyList = $this->server->getPropertiesForPath($fullPath, $properties);

            // The 'Property_Response' class is responsible for generating a
            // single {DAV:}response xml element.
            $response = new DAV\Property\Response($href,$propertyList);
            $response->serialize($this->server, $multiStatus);

        }

        // Deleted items also show up as 'responses'. They have no properties,
        // and a single {DAV:}status element set as 'HTTP/1.1 404 Not Found'.
        foreach($deleted as $item) {

            $fullPath = $collectionUrl . $item;
            $response = new DAV\Property\Response($href, array(), 404);
            $response->serialize($this->server, $multiStatus);

        }

        $syncToken = $dom->createElement('d:synctoken', 'http://sabredav.org/ns/sync/' . $syncToken);
        $mutliStatus->appendChild($syncToken);

        return $dom->saveXML();


    }

    /**
     * This method is triggered whenever properties are requested for a node.
     * We intercept this to see if we can must return a {DAV:}sync-token.
     *
     * @param string $path
     * @param DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, DAV\INode $node, array &$requestedProperties, array &$returnedProperties) {

        if (!in_array('{DAV:}sync-token', $requestedProperties)) {
            return;
        }

        if (!$node instanceof ISyncCollection) {
            return;
        }

        $token = $node->getSyncToken();
        if (!$token) {
            return;
        }

        // Unsetting the property from requested properties.
        $index = array_search('{DAV:}sync-token', $requestedProperties);
        unset($requestedProperties[$index]);

        $returnedProperties[200]['{DAV:}sync-token'] = 'http://sabredav.org/ns/sync/' . $token;

    }

}

