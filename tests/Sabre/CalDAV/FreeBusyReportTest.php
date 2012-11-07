<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;

require_once 'Sabre/CalDAV/Backend/Mock.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class FreeBusyReportTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\CalDAV\Plugin
     */
    protected $plugin;
    /**
     * @var Sabre\DAV\Server
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


        $caldavBackend = new Backend\Mock(array(), $calendarData);

        $calendar = new Calendar($caldavBackend, array(
            'id' => 1,
            'uri' => 'calendar',
            'principaluri' => 'principals/user1',
        ));

        $this->server = new DAV\Server(array($calendar));

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/calendar',
        ));
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();

        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);
        $this->server->addPlugin(new DAVACL\Plugin());

    }

    function testFreeBusyReport() {

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $dom = DAV\XMLUtil::loadDOMDocument($reportXML);
        $this->plugin->report('{urn:ietf:params:xml:ns:caldav}free-busy-query', $dom);

        $this->assertEquals('HTTP/1.1 200 OK', $this->server->httpResponse->status);
        $this->assertEquals('text/calendar', $this->server->httpResponse->headers['Content-Type']);
        $this->assertTrue(strpos($this->server->httpResponse->body,'BEGIN:VFREEBUSY')!==false);

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testFreeBusyReportNoTimeRange() {

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
</c:free-busy-query>
XML;

        $dom = DAV\XMLUtil::loadDOMDocument($reportXML);
        $this->plugin->report('{urn:ietf:params:xml:ns:caldav}free-busy-query', $dom);

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotImplemented
     */
    function testFreeBusyReportWrongNode() {

        $request = new HTTP\Request(array(
            'REQUEST_URI' => '/',
        ));
        $this->server->httpRequest = $request;

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $dom = DAV\XMLUtil::loadDOMDocument($reportXML);
        $this->plugin->report('{urn:ietf:params:xml:ns:caldav}free-busy-query', $dom);

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testFreeBusyReportNoACLPlugin() {

        $this->server = new DAV\Server();
        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $dom = DAV\XMLUtil::loadDOMDocument($reportXML);
        $this->plugin->report('{urn:ietf:params:xml:ns:caldav}free-busy-query', $dom);

    }
}
