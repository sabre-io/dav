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

        $this->server = $server;
        $server->resourceTypeMapping['Sabre_CalDAV_IOutbox'] = '{urn:ietf:params:xml:ns:caldav}schedule-outbox';
        $server->resourceTypeMapping['Sabre_CalDAV_IInbox'] = '{urn:ietf:params:xml:ns:caldav}schedule-inbox';

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

}

?>
