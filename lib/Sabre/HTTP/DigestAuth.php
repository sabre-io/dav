<?php

/**
 * HTTP Digest Authentication handler
 *
 * Use this class for easy http digest authentication 
 * 
 * @package Sabre
 * @subpackage HTTP 
 * @version $Id$
 * @copyright Copyright (C) 2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_HTTP_DigestAuth extends Sabre_HTTP_AbstractAuth {

    protected $nonce;
    protected $opaque;
    protected $digestParts;
    protected $A1;

    /**
     * Initializes the object 
     */
    public function __construct() {

        $this->nonce = uniqid();
        $this->opaque = md5($this->realm);
        parent::__construct();

    }

    /**
     * Gathers all information from the headers
     *
     * This method needs to be called prior to anything else.
     * 
     * @return void
     */
    public function init() {

        $digest = $this->getDigest();
        $this->digestParts = $this->parseDigest($digest);

    }

    /**
     * Validates the user.
     *
     * The A1 parameter should be md5($username . ':' . $realm . ':' . $password);
     *
     * @param string $A1 
     * @return bool 
     */
    public function validateA1($A1) {

        $this->A1 = $A1;
        return $this->validate();

    }

    /**
     * Validates authentication through a password. The actual password must be provided here.
     * It is strongly recommended not store the password in plain-text and use validateA1 instead.
     * 
     * @param string $password 
     * @return bool 
     */
    public function validatePassword($password) {

        $this->A1 = md5($this->digestParts['username'] . ':' . $this->realm . ':' . $password);
        return $this->validate();

    }

    /**
     * Returns the username for the request 
     * 
     * @return string 
     */
    public function getUsername() {

        return $this->digestParts['username'];

    }

    /**
     * Validates the digest challenge 
     * 
     * @return bool 
     */
    protected function validate() {

        $A2 = $this->httpRequest->getMethod() . ':' . $this->digestParts['uri'];
       

        if ($this->digestParts['qop']=='auth-int') {
            $body = $this->httpRequest->getBody(true);
            $this->httpRequest->setBody($body,true);
            $A2 .= ':' . md5($body);
        }

        $A2 = md5($A2);

        $validResponse = md5("{$this->A1}:{$this->digestParts['nonce']}:{$this->digestParts['nc']}:{$this->digestParts['cnonce']}:{$this->digestParts['qop']}:{$A2}"); 

        return $this->digestParts['response']==$validResponse;
        

    }

    /**
     * Returns an HTTP 401 header, forcing login
     *
     * This should be called when username and password are incorrect, or not supplied at all
     *
     * @return void
     */
    public function requireLogin() {

        $this->httpResponse->setHeader('WWW-Authenticate','Digest realm="' . $this->realm . '",qop="auth,auth-int",nonce="' . $this->nonce . '",opaque="' . $this->opaque . '"');
        $this->httpResponse->sendStatus(401);

    }


    /**
     * This method returns the full digest string.
     *
     * It should be compatibile with mod_php format and other webservers.
     *
     * If the header could not be found, null will be returned
     *
     * @return mixed 
     */
    public function getDigest() {

        // mod_php
        $digest = $this->httpRequest->getRawServerValue('PHP_AUTH_DIGEST');
        if ($digest) return $digest;

        // most other servers
        $digest = $this->httpRequest->getHeader('Authentication');

        if ($digest && strpos(strtolower($digest),'digest')===0) {
            return substr($digest,7);
        } else {
            return null;
        }

    }


    /**
     * Parses the different pieces of the digest string into an array.
     * 
     * This method returns false if an incomplete digest was supplied
     *
     * @param string $digest 
     * @return mixed 
     */
    protected function parseDigest($digest) {

        // protect against missing data
        $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
        $data = array();

        preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $digest, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[2] ? $m[2] : $m[3];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data; 

    }

}
