<?php

namespace Sabre;

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/CalDAV/Backend/Mock.php';
require_once 'Sabre/CardDAV/Backend/Mock.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';

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
abstract class DAVServerTest extends \PHPUnit_Framework_TestCase {

    protected $setupCalDAV = false;
    protected $setupCardDAV = false;

    protected $caldavCalendars = array();
    protected $caldavCalendarObjects = array();

    protected $carddavAddressBooks = array();
    protected $carddavCards = array();

    /**
     * @var Sabre\DAV\Server
     */
    protected $server;
    protected $tree = array();

    protected $caldavBackend;
    protected $carddavBackend;
    protected $principalBackend;

    /**
     * @var Sabre\CalDAV\Plugin
     */
    protected $caldavPlugin;
    protected $carddavPlugin;

    function setUp() {

        $this->setUpBackends();
        $this->setUpTree();

        $this->server = new DAV\Server($this->tree);

        if ($this->setupCalDAV) {
            $this->caldavPlugin = new CalDAV\Plugin();
            $this->server->addPlugin($this->caldavPlugin);
        }
        if ($this->setupCardDAV) {
            $this->carddavPlugin = new CardDAV\Plugin();
            $this->server->addPlugin($this->carddavPlugin);
        }

    }

    /**
     * Makes a request, and returns a response object.
     *
     * You can either pass an instance of Sabre\HTTP\Request, or an array,
     * which will then be used as the _SERVER array.
     *
     * @param array|Sabre\HTTP\Request $request
     * @return Sabre\HTTP\Response
     */
    function request($request) {

        if (is_array($request)) {
            $request = new HTTP\Request($request);
        }
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->exec();

        return $this->server->httpResponse;

    }

    function setUpTree() {

        if ($this->setupCalDAV) {
            $this->tree[] = new CalDAV\CalendarRootNode(
                $this->principalBackend,
                $this->caldavBackend
            );
        }
        if ($this->setupCardDAV) {
            $this->tree[] = new CardDAV\AddressBookRoot(
                $this->principalBackend,
                $this->carddavBackend
            );
        }

        if ($this->setupCardDAV || $this->setupCalDAV) {
            $this->tree[] = new DAVACL\PrincipalCollection(
                $this->principalBackend
            );
        }

    }

    function setUpBackends() {

        if ($this->setupCalDAV) {
            $this->caldavBackend = new CalDAV\Backend\Mock($this->caldavCalendars, $this->caldavCalendarObjects);
        }
        if ($this->setupCardDAV) {
            $this->carddavBackend = new CardDAV\Backend\Mock($this->carddavAddressBooks, $this->carddavCards);
        }
        if ($this->setupCardDAV || $this->setupCalDAV) {
            $this->principalBackend = new DAVACL\MockPrincipalBackend();
        }

    }


    function assertHTTPStatus($expectedStatus, HTTP\Request $req) {

        $resp = $this->request($req);
        $this->assertEquals($resp->getStatusMessage($expectedStatus), $resp->status,'Incorrect HTTP status received: ' . $resp->body);

    }

}
