<?php

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/CalDAV/Backend/Mock.php';
require_once 'Sabre/CardDAV/Backend/Mock.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';
require_once 'Sabre/DAV/Auth/MockBackend.php';

/**
 * This class may be used as a basis for other webdav-related unittests.
 *
 * This class is supposed to provide a reasonably big framework to quickly get
 * a testing environment running.
 *
 * @package Sabre
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAVServerTest extends PHPUnit_Framework_TestCase {

    protected $setupCalDAV = false;
    protected $setupCardDAV = false;
    protected $setupACL = false;
    protected $setupCalDAVSharing = false;

    protected $caldavCalendars = array();
    protected $caldavCalendarObjects = array();

    protected $carddavAddressBooks = array();
    protected $carddavCards = array();

    /**
     * @var Sabre_DAV_Server
     */
    protected $server;
    protected $tree = array();

    protected $caldavBackend;
    protected $carddavBackend;
    protected $principalBackend;

    /**
     * @var Sabre_CalDAV_Plugin
     */
    protected $caldavPlugin;

    /**
     * @var Sabre_CardDAV_Plugin
     */
    protected $carddavPlugin;

    /**
     * @var Sabre_DAVACL_Plugin
     */
    protected $aclPlugin;

    /**
     * @var Sabre_CalDAV_SharingPlugin
     */
    protected $caldavSharingPlugin;

    /**
     * @var Sabre_DAV_Auth_Plugin
     */
    protected $authPlugin;

    /**
     * If this string is set, we will automatically log in the user with this
     * name.
     */
    protected $autoLogin = null;

    function setUp() {

        $this->setUpBackends();
        $this->setUpTree();

        $this->server = new Sabre_DAV_Server($this->tree);
        $this->server->debugExceptions = true;

        if ($this->setupCalDAV) {
            $this->caldavPlugin = new Sabre_CalDAV_Plugin();
            $this->server->addPlugin($this->caldavPlugin);
        }
        if ($this->setupCalDAVSharing) {
            $this->caldavSharingPlugin = new Sabre_CalDAV_SharingPlugin();
            $this->server->addPlugin($this->caldavSharingPlugin);
        }
        if ($this->setupCardDAV) {
            $this->carddavPlugin = new Sabre_CardDAV_Plugin();
            $this->server->addPlugin($this->carddavPlugin);
        }
        if ($this->setupACL) {
            $this->aclPlugin = new Sabre_DAVACL_Plugin();
            $this->server->addPlugin($this->aclPlugin);
        }
        if ($this->autoLogin) {
            $authBackend = new Sabre_DAV_Auth_MockBackend();
            $authBackend->defaultUser = $this->autoLogin;
            $this->authPlugin = new Sabre_DAV_Auth_Plugin($authBackend, 'SabreDAV');
            $this->server->addPlugin($this->authPlugin);
        }

    }

    /**
     * Makes a request, and returns a response object.
     *
     * You can either pass an isntance of Sabre_HTTP_Request, or an array,
     * which will then be used as the _SERVER array.
     *
     * @param array|Sabre_HTTP_Request $request
     * @return Sabre_HTTP_Response
     */
    function request($request) {

        if (is_array($request)) {
            $request = new Sabre_HTTP_Request($request);
        }
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new Sabre_HTTP_ResponseMock();
        $this->server->exec();

        return $this->server->httpResponse;

    }

    function setUpTree() {

        if ($this->setupCalDAV) {
            $this->tree[] = new Sabre_CalDAV_CalendarRootNode(
                $this->principalBackend,
                $this->caldavBackend
            );
        }
        if ($this->setupCardDAV) {
            $this->tree[] = new Sabre_CardDAV_AddressBookRoot(
                $this->principalBackend,
                $this->carddavBackend
            );
        }

        if ($this->setupCardDAV || $this->setupCalDAV) {
            $this->tree[] = new Sabre_DAVACL_PrincipalCollection(
                $this->principalBackend
            );
        }

    }

    function setUpBackends() {

        if ($this->setupCalDAV && is_null($this->caldavBackend)) {
            $this->caldavBackend = new Sabre_CalDAV_Backend_Mock($this->caldavCalendars, $this->caldavCalendarObjects);
        }
        if ($this->setupCardDAV && is_null($this->carddavBackend)) {
            $this->carddavBackend = new Sabre_CardDAV_Backend_Mock($this->carddavAddressBooks, $this->carddavCards);
        }
        if ($this->setupCardDAV || $this->setupCalDAV) {
            $this->principalBackend = new Sabre_DAVACL_MockPrincipalBackend();
        }

    }


    function assertHTTPStatus($expectedStatus, Sabre_HTTP_Request $req) {

        $resp = $this->request($req);
        $this->assertEquals($resp->getStatusMessage($expectedStatus), $resp->status,'Incorrect HTTP status received: ' . $resp->body);

    }

}
