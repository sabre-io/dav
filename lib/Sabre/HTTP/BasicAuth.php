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
     * HTTP request helper 
     * 
     * @var Sabre_HTTP_Request 
     */
    protected $httpRequest;

    /**
     * __construct 
     * 
     */
    public function __construct() {

        $this->httpResponse = new Sabre_HTTP_Response();
        $this->httpRequest = new Sabre_HTTP_Request();

    }

    /**
     * Sets an alternative HTTP response object 
     * 
     * @param Sabre_HTTP_Response $response 
     * @return void
     */
    public function setHTTPResponse(Sabre_HTTP_Response $response) {

        $this->httpResponse = $response;

    }

    /**
     * Sets an alternative HTTP request object 
     * 
     * @param Sabre_HTTP_Request $request 
     * @return void
     */
    public function setHTTPRequest(Sabre_HTTP_Request $request) {

        $this->httpRequest = $request;

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
        if ($user = $this->httpRequest->getRawServerValue('PHP_AUTH_USER') && $pass = $this->httpRequest->getRawServerValue('PHP_AUTH_PW')) {

            return array($user,$pass);

        }

        // IIS
        if ($auth = $this->httpRequest->getHeader('Authorization')) {

            return explode(':', base64_decode(substr($auth, 6)));

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
