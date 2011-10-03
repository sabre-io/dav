<?php

/**
 * SabreDAV CalDAV scheduling plugin
 *
 * This plugin is responsible for registering all the features required for the 
 * CalDAV Scheduling extension.
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Schedule_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * The scheduling root node
     */
    const SCHEDULE_ROOT = 'schedule';

    /**
     * Reference to Server object 
     * 
     * @var Sabre_DAV_Server 
     */
    protected $server;

    /**
     * Initializes the plugin
     *
     * Registers all required events and features. 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $ns = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}';

        $this->server = $server;
        $server->resourceTypeMapping['Sabre_CalDAV_Schedule_IOutbox'] = $ns . 'schedule-outbox';
        $server->resourceTypeMapping['Sabre_CalDAV_Schedule_IInbox'] = $ns . 'schedule-inbox';

        // This ensures that a users' addresses are all searchable.
        $aclPlugin = $this->server->getPlugin('acl');
        if (!$aclPlugin) {
            throw new Sabre_DAV_Exception('ACL plugin must be loaded for the Scheduling plugin to work. We\'re doooomed');
        }
        $acl->principalSearchPropertySet[$ns . 'calendar-user-address-set'] =
            'Calendar user addresses';

        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));
        $server->subscribeEvent('unknownMethod', array($this,'unknownMethod'));
        // $server->subscribeEvent('afterBind',array($this,'afterBind'));

    }

    /**
     * Returns a list of features
     *
     * This is used in the DAV: header, which appears in responses to both the 
     * OPTIONS request and the PROPFIND request. 
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('calendar-auto-schedule');

    }

    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific 
     * properties. 
     * 
     * @param string $path
     * @param Sabre_DAV_INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, Sabre_DAV_INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof Sabre_DAVACL_IPrincipal || $node instanceof Sabre_CalDAV_UserCalendars) {

            // schedule-inbox-URL property
            $inboxProp = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-inbox-URL';
            if (in_array($inboxProp,$requestedProperties)) {
                $principalId = $node->getName(); 
                $inboxPath = self::SCHEDULE_ROOT . '/' . $principalId . '/inbox';
                unset($requestedProperties[$inboxProp]);
                $returnedProperties[200][$inboxProp] = new Sabre_DAV_Property_Href($inboxPath);
            }

            // schedule-outbox-URL property
            $outboxProp = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-outbox-URL';
            if (in_array($inboxProp,$requestedProperties)) {
                $principalId = $node->getName(); 
                $outboxPath = self::SCHEDULE_ROOT . '/' . $principalId . '/outbox';
                unset($requestedProperties[$outboxProp]);
                $returnedProperties[200][$outboxProp] = new Sabre_DAV_Property_Href($outboxPath);
            }

        }

    }

    /**
     * This is the handler for the 'unknownMethod' event.
     *
     * We are intercepting this event to add support for the POST method on the 
     * schedule-outbox.
     * 
     * @param string $method 
     * @param string $uri 
     * @return void
     */
    public function unknownMethod($method, $uri) {

        if ($method!=='POST') return;
        if ($this->server->httpRequest->getHeader('Content-Type') !== 'text/calendar')
            return;

        try {
            $node = $this->server->tree->getNodeForPath($uri);
        } catch (Sabre_DAV_Exception_FileNotFound $e) {
            return;
        }

        if (!$node instanceof Sabre_CalDAV_Schedule_IOutbox)
            return;

        // Checking permission
        $acl = $this->server->getPlugin('acl');
        $privileges = array(
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-query-freebusy',
        );
        $acl->checkPrivileges($uri,$privileges);

        $this->server->httpResponse->sendBody('Bottoms up!');
        return false; 

    }

}

?>
