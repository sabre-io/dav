<?php

namespace Sabre\DAV\Auth\Backend;

use
    Sabre\HTTP,
    Sabre\DAV,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface;

/**
 * HTTP Digest authentication backend class
 *
 * This class can be used by authentication objects wishing to use HTTP Digest
 * Most of the digest logic is handled, implementors just need to worry about
 * the getDigestHash method
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class AbstractDigest implements BackendInterface {

    /**
     * Authentication Realm.
     *
     * The realm is often displayed by browser clients when showing the
     * authentication dialog.
     *
     * @var string
     */
    protected $realm = 'SabreDAV';

    /**
     * This is the prefix that will be used to generate principal urls.
     *
     * @var string
     */
    protected $principalPrefix = 'principals/';

    /**
     * Returns a users digest hash based on the username and realm.
     *
     * If the user was not known, null must be returned.
     *
     * @param string $realm
     * @param string $username
     * @return string|null
     */
    abstract function getDigestHash($realm, $username);

    /**
     * When this method is called, the backend must check if authentication was
     * successful.
     *
     * This method should simply return null if authentication was not
     * successful.
     *
     * If authentication was successful, it's expected that the authentication
     * backend returns a so-called principal url.
     *
     * Examples of a principal url:
     *
     * principals/admin
     * principals/user1
     * principals/users/joe
     * principals/uid/123457
     *
     * If you don't use WebDAV ACL (RFC3744) we recommend that you simply
     * return a string such as:
     *
     * principals/users/[username]
     *
     * But literally any non-null value will be accepted as a 'succesful
     * authentication'.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return null|string
     */
    function check(RequestInterface $request, ResponseInterface $response) {

        $digest = new HTTP\Auth\Digest(
            $this->realm,
            $request,
            $response
        );
        $digest->init();

        $username = $digest->getUsername();

        // No username was given
        if (!$username) {
            return null;
        }

        $hash = $this->getDigestHash($this->realm, $username);
        // If this was false, the user account didn't exist
        if ($hash===false || is_null($hash)) {
            return null;
        }
        if (!is_string($hash)) {
            throw new DAV\Exception('The returned value from getDigestHash must be a string or null');
        }

        // If this was false, the password or part of the hash was incorrect.
        if (!$digest->validateA1($hash)) {
            return null;
        }

        return $this->principalPrefix . $username;

    }

    /**
     * This method is called when a user could not be authenticated, and
     * authentication was required for the current request.
     *
     * This gives you the oppurtunity to set authentication headers. The 401
     * status code will already be set.
     *
     * In this case of Basic Auth, this would for example mean that the
     * following header needs to be set:
     *
     * $response->addHeader('WWW-Authenticate', 'Basic realm=SabreDAV');
     *
     * Keep in mind that in the case of multiple authentication backends, other
     * WWW-Authenticate headers may already have been set, and you'll want to
     * append your own WWW-Authenticate header instead of overwriting the
     * existing one.
     *
     * @return void
     */
    function requireAuth(RequestInterface $request, ResponseInterface $response) {

        $auth = new HTTP\Auth\Digest(
            $this->realm,
            $request,
            $response
        );
        $auth->init();
        $auth->requireLogin();

    }

}
