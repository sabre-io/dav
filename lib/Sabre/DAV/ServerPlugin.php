<?php

/**
 * The baseclass for all server plugins.
 *
 * Plugins can modify or extend the servers behaviour. 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_ServerPlugin {

    /**
     * This initializes the plugin.
     *
     * This function is called by Sabre_DAV_Server, after
     * addPlugin is called.
     *
     * This method should set up the requires event subscriptions.
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    abstract public function initialize(Sabre_DAV_Server $server);
    
    /**
     * This method should return a list of server-features. 
     *
     * This is for example 'versioning' and is added to the DAV: header
     * in an OPTIONS response.
     * 
     * @return array
     */
    public function getFeatures() {

        return array();

    }

    /**
     * If plugin implements more HTTP methods, it should tell 
     * the server which those are, for the Allow: response header.
     * 
     * @return array 
     */
    public function getHTTPMethods() {

        return array();

    }

}

?>
