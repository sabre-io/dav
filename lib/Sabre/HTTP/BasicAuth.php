<?php

/**
 * HTTP Basic Authentication handler
 *
 * Use this class for easy http authentication setup
 * 
 * @package Sabre
 * @subpackage HTTP 
 * @version $Id$
 * @copyright Copyright (C) 2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_HTTP_BasicAuth {

    /**
     * The realm will be displayed in the dialog boxes
     *
     * This identifier can be changed through setRealm()
     * 
     * @var string
     */
    protected $realm = 'SabreDAV';

    /**
     * HTTP response helper 
     * 
     * @var Sabre_HTTP_Response 
     */
    protected $httpResponse;

    /**
     * __construct 
     * 
     * @return void
     */
    public function __construct() {

        $this->httpResponse = new Sabre_HTTP_Response();

    }

    /**
     * Returns the supplied username and password.
     *
     * The returned array has two values:
     *   * 0 - username
     *   * 1 - password
     *
     * If nothing was supplied, 'false' will be returned
     *
     * @return mixed 
     */
    public function getUserPass() {

        // Apache
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {

            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

            return array($username,$password);

        }

        // IIS
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {

            return explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

        }

        return false;

    }

    /**
     * Sets the realm
     *
     * The realm is often displayed in authentication dialog boxes
     * Commonly an application name displayed here
     * 
     * @param mixed $realm 
     * @return void
     */
    public function setRealm($realm) {

        $this->realm = $realm;

    }

    /**
     * Returns an HTTP 401 header, forcing login
     *
     * This should be called when username and password are incorrect, or not supplied at all
     *
     * @return void
     */
    public function requireLogin() {

        $this->httpResponse->setHeader('WWW-Authenticate','Basic realm="' . $this->realm . '"');
        $this->httpResponse->sendStatus(401);

    }

}
