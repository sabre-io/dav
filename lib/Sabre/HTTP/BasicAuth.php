<?php

namespace Sabre\HTTP;

/**
 * HTTP Basic Authentication handler
 *
 * Use this class for easy http authentication setup
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class BasicAuth extends AbstractAuth {

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

        // Apache and mod_php
        if (($user = $this->httpRequest->getRawServerValue('PHP_AUTH_USER')) && ($pass = $this->httpRequest->getRawServerValue('PHP_AUTH_PW'))) {

            return array($user,$pass);

        }

        // Most other webservers
        $auth = $this->httpRequest->getHeader('Authorization');

        // Apache could prefix environment variables with REDIRECT_ when urls
        // are passed through mod_rewrite
        if (!$auth) {
            $auth = $this->httpRequest->getRawServerValue('REDIRECT_HTTP_AUTHORIZATION');
        }
        
        
        //if Apache dont route the HTTP_AUTHORIZATION to the php code, so change the 
        //rewrite rule from (sample)
        // RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
        //to
        // RewriteRule .* - [env=REMOTE_USER:%{HTTP:Authorization},L]
        //so the php code can reach the basic auth value
        if (!$auth) {
            $auth = $this->httpRequest->getRawServerValue('REMOTE_USER');
        }
        if (!$auth) {
            $auth = $this->httpRequest->getRawServerValue('REDIRECT_REMOTE_USER');
        }

        if (!$auth) return false;

        if (strpos(strtolower($auth),'basic')!==0) return false;

        return explode(':', base64_decode(substr($auth, 6)),2);

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
