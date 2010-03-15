<?php

/**
 * HTTP Digest authentication backend class
 *
 * This class can be used by authentication objects wishing to use HTTP Digest
 * Most of the digest logic is handled, implementors just need to worry about 
 * the getUserInfo method 
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Auth_Backend_AbstractDigest extends Sabre_DAV_Auth_Backend_Abstract {

    /**
     * Returns a users information based on its username
     *
     * The returned struct must contain at least a userId
     * element (which can be identical to username) as well as a digestHash
     * element.
     *
     * If the user was not known, false must be returned. 
     * 
     * @param string $realm
     * @param string $username 
     * @return array 
     */
    abstract public function getUserInfo($realm, $username);

    /**
     * Authenticates the user based on the current request.
     *
     * If authentication succeeds, a struct with user-information must be returned
     * If authentication fails, this method must throw an exception. 
     *
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return void
     */
    public function authenticate(Sabre_DAV_Server $server,$realm) {

        $digest = new Sabre_HTTP_DigestAuth();

        // Hooking up request and response objects
        $digest->setHTTPRequest($server->httpRequest);
        $digest->setHTTPResponse($server->httpResponse);

        $digest->setRealm($realm);
        $digest->init();

        $username = $digest->getUsername();

        // No username was given
        if (!$username) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('No digest authentication headers were found');
        }

        $userData = $this->getUserInfo($realm, $username);
        // If this was false, the user account didn't exist
        if ($userData===false) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('The supplied username was not on file');
        }
        if (!is_array($userData)) {
            throw new Sabre_DAV_Exception('The returntype for getUserInfo must be either false or an array');
        }

        if (!isset($userData['userId']) || !isset($userData['digestHash'])) {
            throw new Sabre_DAV_Exception('The returned array from getUserInfo must contain at least a userId and digestHash element');
        }

        // If this was false, the password or part of the hash was incorrect.
        if (!$digest->validateA1($userData['digestHash'])) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('Incorrect username');
        }

        return $userData;

    }

}
