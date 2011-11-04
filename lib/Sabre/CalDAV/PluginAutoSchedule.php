<?php

/**
 * CalDAV plugin for calendar-auto-schedule
 * 
 * This plugin provides functionality added by draft-desruisseaux-caldav-sched-10
 * It takes care of additional properties and features
 * 
 * see: http://tools.ietf.org/html/draft-desruisseaux-caldav-sched-10
 *
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_PluginAutoSchedule extends Sabre_DAV_ServerPlugin {

    /**
     * Reference to server object 
     * 
     * @var Sabre_DAV_Server 
     */
    private $server;

    /**
     * Returns a list of features for the DAV: HTTP header. 
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('calendar-auto-schedule');

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

        return 'caldavAutoSchedule';

    }

    /**
     * Initializes the plugin 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;

        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));

        $server->xmlNamespaces[Sabre_CalDAV_Plugin::NS_CALDAV] = 'cal';

        $server->resourceTypeMapping['Sabre_CalDAV_ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';

        array_push($server->protectedProperties,
        
            // auto-scheduling extension
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-calendar-transp',
        	'{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-default-calendar-URL',
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-tag'
        
        );
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
        
        if ($node instanceof Sabre_DAVACL_IPrincipal) {
            
            // schedule-inbox-URL property
            $scheduleInboxURL = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-inbox-URL';
            if (in_array($scheduleInboxURL,$requestedProperties)) {
                $principalId = $node->getName();
                $properties = $node->getProperties(array($scheduleInboxURL));
                
                if (isset($properties[$scheduleInboxURL])) {
                    $calendarPath = Sabre_CalDAV_Plugin::CALENDAR_ROOT . '/' . $principalId . '/' . $properties[$scheduleInboxURL];
                    unset($requestedProperties[$scheduleInboxURL]);
                    $returnedProperties[200][$scheduleInboxURL] = new Sabre_DAV_Property_Href($calendarPath);
                }
            }

            // schedule-outbox-URL property
            $scheduleOutboxURL = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-outbox-URL';
            if (in_array($scheduleOutboxURL,$requestedProperties)) {
                $principalId = $node->getName();
                $properties = $node->getProperties(array($scheduleOutboxURL));
                
                if (isset($properties[$scheduleOutboxURL])) {
                    $calendarPath = Sabre_CalDAV_Plugin::CALENDAR_ROOT . '/' . $principalId . '/' . $properties[$scheduleOutboxURL];
                    unset($requestedProperties[$scheduleOutboxURL]);
                    $returnedProperties[200][$scheduleOutboxURL] = new Sabre_DAV_Property_Href($calendarPath);
                }

            }
        }
    }
}
