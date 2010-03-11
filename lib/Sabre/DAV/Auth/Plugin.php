<?php

/**
 * This plugin provides Authentication for a WebDAV server.
 * 
 * It relies on a Backend object, which provides user information.
 *
 * Additionally, it provides support for the RFC 5397 current-user-principal
 * property.
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
     * userName of currently logged in user 
     * 
     * @var string 
     */
    private $userInfo;

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
     * This method intercepts calls to PROPFIND and similar lookups 
     * 
     * This is done to inject the current-user-principal if this is requested.
     *
     * @todo support for 'unauthenticated'
     * @return void  
     */
    public function afterGetProperties($href, &$properties) {

        if (array_key_exists('{DAV:}current-user-principal', $properties[404])) {
            if ($this->userInfo) {
                $properties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::HREF, 'principals/' . $this->userInfo['userId']);
            } else {
                $properties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::UNAUTHENTICATED);
            }
            unset($properties[404]['{DAV:}current-user-principal']);
        }

    }

    /**
     * This method is called before any HTTP method and forces users to be authenticated
     * 
     * @param string $method
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool 
     */
    public function beforeMethod($method) {

        $userInfo = $this->authBackend->authenticate($this->server,$this->realm);
        if ($userInfo===false) throw new Sabre_DAV_Exception_NotAuthenticated('Incorrect username or password, or no credentials provided');
        if (!is_array($userInfo)) throw new Sabre_DAV_Exception('The authenticate method must either return an array, or false');

        $this->userInfo = $userInfo;
    }

    /**
     * Returns the currently logged in user's information.
     *
     * This will only be set if authentication was succesful.
     * 
     * @return array 
     */
    public function getUserInfo() {

        return $this->userInfo;

    }

}

?>
