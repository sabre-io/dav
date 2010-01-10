<?php

/**
 * This plugin provides Authentication for a WebDAV server.
 * 
 * It relies on a Backend object, which provides user information.
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
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
    private $userName;

    /**
     * User id of currently logged in user. 
     * 
     * @var string 
     */
    private $userId;
    

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

    }

    /**
     * This method is called before any HTTP method and forces users to be authenticated
     * 
     * @param string $method
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool 
     */
    public function beforeMethod($method) {

        $digest = new Sabre_HTTP_DigestAuth();

        // Hooking up request and response objects
        $digest->setHTTPRequest($this->server->httpRequest);
        $digest->setHTTPResponse($this->server->httpResponse);

        $digest->setRealm($this->realm);
        $digest->init();

        $username = $digest->getUsername();

        // No username was given
        if (!$username) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('No digest authentication headers were found');
        }

        // Now checking the backend for the A1 hash
        $A1 = $this->authBackend->getDigestHash($username);

        // If this was false, the user account didn't exist
        if (!$A1) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('The supplied username was not on file');
        }

        // If this was false, the password or part of the hash was incorrect.
        if (!$digest->validateA1($A1)) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('Incorrect username');
        }

        $this->userName = $username;
        $this->userId = $this->authBackend->getUserId($username);

        // Eventhooks must return true to continue processing
        return true;

    }

    /**
     * Returns the currently logged in username.
     *
     * This will only be set after the beforeMethod event has been handled.
     * 
     * @return string 
     */
    public function getUserName() {

        return $this->userName;

    }

    /**
     * Returns the currently logged in user's id.
     *
     * This will only be set after the beforeMethod event has been handled.
     * 
     * @return string 
     */
    public function getUserId() {

        return $this->userId;

    }

}

?>
