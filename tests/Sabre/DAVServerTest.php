<?php

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/CalDAV/Backend/Mock.php';
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
abstract class Sabre_DAVServerTest extends PHPUnit_Framework_TestCase {

    protected $setupCalDAV = false;

    protected $caldavCalendars = array();
    protected $caldavCalendarObjects = array();

    protected $server;
    protected $tree = array();
    protected $caldavBackend;
    protected $principalBackend;

    function setUp() {

        $this->setUpBackends();
        $this->setUpTree();
        
        $this->server = new Sabre_DAV_Server($this->tree);

        if ($this->setupCalDAV) {
            $this->server->addPlugin(new Sabre_CalDAV_Plugin());
        }

    }

    function setUpTree() {
        
        $this->tree = array();
        if ($this->setupCalDAV) {
            $this->tree[] = new Sabre_CalDAV_CalendarRootNode(
                $this->principalBackend,
                $this->caldavBackend
            );
            $this->tree[] = new Sabre_DAVACL_PrincipalCollection(
                $this->principalBackend
            );
        }

    }

    function setUpBackends() {

        if ($this->setupCalDAV) {
            $this->caldavBackend = new Sabre_CalDAV_Backend_Mock($this->caldavCalendars, $this->caldavCalendarObjects);
            $this->principalBackend = new Sabre_DAVACL_MockPrincipalBackend();
        }

    }

    function request(Sabre_HTTP_Request $r) {

        $this->server->httpRequest = $r;
        $this->server->httpResponse = new Sabre_HTTP_ResponseMock();
        $this->server->exec();

        return $this->server->httpResponse;

    }

    function assertHTTPStatus($expectedStatus, Sabre_HTTP_Request $req) {

        $resp = $this->request($req);
        $this->assertEquals($resp->getStatusMessage($expectedStatus), $resp->status,'Incorrect HTTP status received: ' . $resp->body);

    }

}

?>
