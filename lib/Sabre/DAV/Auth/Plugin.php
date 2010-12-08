<?php

/**
 * This plugin provides Authentication for a WebDAV server.
 * 
 * It relies on a Backend object, which provides user information.
 *
 * Additionally, it provides support for:
 *  * {DAV:}current-user-principal property from RFC5397
 *  * {DAV:}principal-collection-set property from RFC3744
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Reference to main server object 
     * 
     * @var Sabre_DAV_Server 
     */
    private $server;

    /**
     * Authentication backend
     * 
     * @var Sabre_DAV_Auth_Backend_Abstract 
     */
    private $authBackend;

    /**
     * The authentication realm. 
     * 
     * @var string 
     */
    private $realm;

    /**
     * __construct 
     * 
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     * @param string $realm 
     * @return void
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend, $realm) {

        $this->authBackend = $authBackend;
        $this->realm = $realm;

    }

    /**
     * Initializes the plugin. This function is automatically called by the server  
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $this->server->subscribeEvent('beforeMethod',array($this,'beforeMethod'),10);
        $this->server->subscribeEvent('afterGetProperties',array($this,'afterGetProperties'));

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

        return 'auth';

    }

    /**
     * Returns the current users' principal uri.
     * 
     * If nobody is logged in, this will return null. 
     * 
     * @return string|null 
     */
    public function getCurrentUserPrincipal() {

        $userInfo = $this->authBackend->getCurrentUser();
        if (!$userInfo) return null;

        return $userInfo['uri'];

    }

    /**
     * This method intercepts calls to PROPFIND and similar lookups 
     * 
     * This is done to inject the current-user-principal if this is requested.
     *
     * @return void  
     */
    public function afterGetProperties($href, &$properties) {

        if (array_key_exists('{DAV:}current-user-principal', $properties[404])) {
            if ($url = $this->getCurrentUserPrincipal()) {
                $properties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::HREF, $url);
            } else {
                $properties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::UNAUTHENTICATED);
            }
            unset($properties[404]['{DAV:}current-user-principal']);
        }
        if (array_key_exists('{DAV:}principal-collection-set', $properties[404])) {
            $properties[200]['{DAV:}principal-collection-set'] = new Sabre_DAV_Property_Href($this->authBackend->principalBaseUri);
            unset($properties[404]['{DAV:}principal-collection-set']);
        }
        if (array_key_exists('{DAV:}supported-report-set', $properties[200])) {
            $properties[200]['{DAV:}supported-report-set']->addReport(array(
                '{DAV:}expand-property',
            ));
        }

    }

    /**
     * This method is called before any HTTP method and forces users to be authenticated
     * 
     * @param string $method
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool 
     */
    public function beforeMethod($method, $uri) {

        $this->authBackend->authenticate($this->server,$this->realm);

    }

}
