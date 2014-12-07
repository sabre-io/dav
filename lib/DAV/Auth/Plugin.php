<?php

namespace Sabre\DAV\Auth;

use
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface,
    Sabre\HTTP\URLUtil,
    Sabre\DAV\Exception\NotAuthenticated,
    Sabre\DAV\Server,
    Sabre\DAV\ServerPlugin;


/**
 * This plugin provides Authentication for a WebDAV server.
 *
 * It relies on a Backend object, which provides user information.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends ServerPlugin {

    /**
     * Reference to main server object
     *
     * @var Server
     */
    protected $server;

    /**
     * Authentication backend
     *
     * @var Backend\BackendInterface
     */
    protected $authBackend;

    /**
     * The currently logged in principal. Will be `null` if nobody is currently
     * logged in.
     *
     * @var string|null
     */
    protected $currentPrincipal;

    /**
     * Creates the authentication plugin
     *
     * @param Backend\BackendInterface $authBackend
     */
    function __construct(Backend\BackendInterface $authBackend) {

        $this->authBackend = $authBackend;

    }

    /**
     * Initializes the plugin. This function is automatically called by the server
     *
     * @param Server $server
     * @return void
     */
    function initialize(Server $server) {

        $this->server = $server;
        $this->server->on('beforeMethod', [$this,'beforeMethod'], 10);

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using DAV\Server::getPlugin
     *
     * @return string
     */
    function getPluginName() {

        return 'auth';

    }

    /**
     * Returns the currently logged-in principal.
     *
     * This will return a string such as:
     *
     * principals/username
     * principals/users/username
     *
     * This method will return null if nobody is logged in.
     *
     * @return string|null
     */
    function getCurrentPrincipal() {

        return $this->currentPrincipal;

    }

    /**
     * Returns the current username.
     *
     * This method is deprecated and is only kept for backwards compatibility
     * purposes. Please switch to getCurrentPrincipal().
     *
     * @deprecated Will be removed in a future version!
     * @return string|null
     */
    function getCurrentUser() {

        // We just do a 'basename' on the principal to give back a sane value
        // here.
        list(, $userName) = URLUtil::splitPath(
            $this->getCurrentPrincipal()
        );

        return $userName;

    }

    /**
     * This method is called before any HTTP method and forces users to be authenticated
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    function beforeMethod(RequestInterface $request, ResponseInterface $response) {

        $result = $this->authBackend->check(
            $request,
            $response
        );

        if (!is_array($result) || count($result)!==2 || !is_bool($result[0]) || !is_string($result[1])) {
            throw new \Sabre\DAV\Exception('The authentication backend did not return a correct value from the check() method.');
        }
        if ($result[0]) {
            $this->currentPrincipal = $result[1];
        } else {
            $this->currentPrincipal = null;
            $this->authBackend->requireAuth($request, $response);
            throw new NotAuthenticated('Authentication failed. Reason: ' . $result[1]);
        }

    }

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Generic authentication plugin',
            'link'        => 'http://sabre.io/dav/authentication/',
        ];

    }

}
