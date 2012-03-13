<?php

require_once 'Sabre/CalDAV/Backend/Mock.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_CalDAV_FreeBusyReportTest extends PHPUnit_Framework_TestCase {

    /**
     * @var Sabre_CalDAV_Plugin
     */
    protected $plugin;
    /**
     * @var Sabre_DAV_Server
     */
    protected $server;

    function setUp() {

        $obj1 = <<<ics
BEGIN:VCALENDAR
BEGIN:VEVENT
DTSTART:20111005T120000Z
DURATION:PT1H
END:VEVENT
END:VCALENDAR
ics;
        $obj2 = fopen('php://memory','r+');
        fwrite($obj2,<<<ics
BEGIN:VCALENDAR
BEGIN:VEVENT
DTSTART:20121005T120000Z
DURATION:PT1H
END:VEVENT
END:VCALENDAR
ics
        );
        rewind($obj2);

        $calendarData = array(
            1 => array(
                'obj1' => array(
                    'calendarid' => 1,
                    'uri' => 'event1.ics',
                    'calendardata' => $obj1,
                 ),
                'obj2' => array(
                    'calendarid' => 1,
                    'uri' => 'event2.ics',
                    'calendardata' => $obj2
                )
            ),
        );


        $caldavBackend = new Sabre_CalDAV_Backend_Mock(array(), $calendarData);
        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();

        $calendar = new Sabre_CalDAV_Calendar($principalBackend,$caldavBackend, array(
            'id' => 1,
            'uri' => 'calendar',
            'principaluri' => 'principals/user1',
        ));

        $this->server = new Sabre_DAV_Server(array($calendar));

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_URI' => '/calendar',
        ));
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new Sabre_HTTP_ResponseMock();

        $this->plugin = new Sabre_CalDAV_Plugin();
        $this->server->addPlugin($this->plugin);
        $this->server->addPlugin(new Sabre_DAVACL_Plugin());

    }

    function testFreeBusyReport() {

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($reportXML);
        $this->plugin->report('{urn:ietf:params:xml:ns:caldav}free-busy-query', $dom);

        $this->assertEquals('HTTP/1.1 200 OK', $this->server->httpResponse->status);
        $this->assertEquals('text/calendar', $this->server->httpResponse->headers['Content-Type']);
        $this->assertTrue(strpos($this->server->httpResponse->body,'BEGIN:VFREEBUSY')!==false);

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testFreeBusyReportNoTimeRange() {

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
</c:free-busy-query>
XML;

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($reportXML);
        $this->plugin->report('{urn:ietf:params:xml:ns:caldav}free-busy-query', $dom);

    }

    /**
     * @expectedException Sabre_DAV_Exception_NotImplemented
     */
    function testFreeBusyReportWrongNode() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_URI' => '/',
        ));
        $this->server->httpRequest = $request;

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($reportXML);
        $this->plugin->report('{urn:ietf:params:xml:ns:caldav}free-busy-query', $dom);

    }

    /**
     * @expectedException Sabre_DAV_Exception
     */
    function testFreeBusyReportNoACLPlugin() {

        $this->server = new Sabre_DAV_Server();
        $this->plugin = new Sabre_CalDAV_Plugin();
        $this->server->addPlugin($this->plugin);

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($reportXML);
        $this->plugin->report('{urn:ietf:params:xml:ns:caldav}free-busy-query', $dom);

    }
}
