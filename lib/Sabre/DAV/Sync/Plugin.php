<?php

/**
 * This plugin all WebDAV-sync capabilities to the Server.
 *
 * The sync capabilities only work with collections that implement
 * Sabre_DAV_Sync_ISyncCollection.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Sync_Plugin extends Sabre_DAV_ServerPlugin {

    protected $server;

    /**
     * Initializes the plugin.
     *
     * This is when the plugin registers it's hooks.
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;

        $self = $this;

        $server->subscribeEvent('report', function($reportName, $dom, $uri) use ($self) {

            if ($reportName === '{DAV:}sync-collection') {
                $self->syncCollection($uri, $dom);
                return false;
            }

        });

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
        if (!$node instanceof Sabre_DAV_Sync_ISyncCollection) {
            throw new Sabre_DAV_Exception_ReportNotImplemented('The {DAV:}sync-collection REPORT is not implemented on this url.');
        }

        $changeInfo = $node->getChanges($syncToken, $syncLevel, $limit);

        // Getting all the requested properties

        throw new Exception('Not Done!');

        // Encoding the response
        $this->sendSyncResponse(
            $changeInfo['syncToken'],
            $changeInfo['modified'],
            $changeInfo['deleted'],
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
            throw new Sabre_DAV_Exception_BadRequest('You must specify a {DAV:}sync-token element, and it must appear exactly once');
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
                $syncLevel = Sabre_DAV_Server::DEPTH_INFINITE;
            }

            // If the syncLevel was set, and depth is something other than 0..
            // we must fail.

            if ($depth != 0) {
                throw new Sabre_DAV_Exception_Forbidden('The sync-collection report is only implemented on depth: 0');
            }

        }
        $limit = $xpath->query("//d:limit/d:nresults");
        if ($limit->length === 0) {
            $limit = null;
        } else {
            $limit = $limit->item(0)->nodeValue;
        }

        $properties = Sabre_DAV_XMLUtil::getProperties($dom);

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
     * @param array $modified
     * @param array $deleted
     * @return void
     */
    protected function sendSyncCollectionResponse($syncToken, array $modified, array $deleted) {

        $dom = new DOMDocument('1.0','utf-8');
        //$dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');
        $dom->appendChild($multiStatus);

        // Adding in default namespaces
        foreach($this->xmlNamespaces as $namespace=>$prefix) {

            $multiStatus->setAttribute('xmlns:' . $prefix,$namespace);

        }

        foreach($fileProperties as $entry) {

            $href = $entry['href'];
            unset($entry['href']);

            $response = new Sabre_DAV_Property_Response($href,$entry);
            $response->serialize($this,$multiStatus);

        }

        return $dom->saveXML();


    }

}

?>
