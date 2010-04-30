<?php

/**
 * CardDAV plugin 
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */


/**
 * The CardDAV plugin adds CardDAV functionality to the WebDAV server
 */
class Sabre_CardDAV_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Url to the addressbooks
     */
    const ADDRESSBOOK_ROOT = 'addressbooks';

    /**
     * xml namespace for CardDAV elements
     */
    const NS_CARDDAV = 'urn:ietf:params:xml:ns:carddav';

    /**
     * Server class 
     * 
     * @var Sabre_DAV_Server 
     */
    protected $server;

    /**
     * Initializes the plugin 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void 
     */
    public function initialize(Sabre_DAV_Server $server) {

        $server->subscribeEvent('afterGetProperties', array($this, 'afterGetProperties'));

        $this->server = $server;

    }

    /**
     * Adds all CardDAV-specific properties 
     * 
     * @param string $path 
     * @param array $properties 
     * @return void
     */
    public function afterGetProperties($path, array &$properties) { 

        // Find out if we are currently looking at a principal resource
        $currentNode = $this->server->tree->getNodeForPath($path);
        if ($currentNode instanceof Sabre_DAV_Auth_Principal) {

            // calendar-home-set property
            $addHome = '{' . self::NS_CARDDAV . '}addressbook-home-set';
            if (array_key_exists($addHome,$properties[404])) {
                $principalId = $currentNode->getName(); 
                $addressbookHomePath = self::ADDRESSBOOK_ROOT . '/' . $principalId . '/';
                unset($properties[404][$addHome]);
                $properties[200][$addHome] = new Sabre_DAV_Property_Href($addressbookHomePath);
            }

        }

    }

}
