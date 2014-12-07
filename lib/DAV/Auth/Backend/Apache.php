<?php

namespace Sabre\DAV\Auth\Backend;

use
    Sabre\DAV,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface;

/**
 * Apache authenticator
 *
 * This authentication backend assumes that authentication has been
 * configured in apache, rather than within SabreDAV.
 *
 * Make sure apache is properly configured for this to work.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Apache implements BackendInterface {

    /**
     * This is the prefix that will be used to generate principal urls.
     *
     * @var string
     */
    protected $principalPrefix = 'principals/';

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

        $remoteUser = $request->getRawServerValue('REMOTE_USER');
        if (is_null($remoteUser)) {
            $remoteUser = $request->getRawServerValue('REDIRECT_REMOTE_USER');
        }
        if (is_null($remoteUser)) {
            return null;
        }

        return $this->principalPrefix . $remoteUser;

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

    }

}

