<?php declare (strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAV;
use GuzzleHttp\Psr7\ServerRequest;

class FreeBusyReportTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Plugin
     */
    protected $plugin;
    /**
     * @var DAV\Server
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

        $obj2 = fopen('php://memory', 'r+');
        fwrite($obj2, <<<ics
BEGIN:VCALENDAR
BEGIN:VEVENT
DTSTART:20121005T120000Z
DURATION:PT1H
END:VEVENT
END:VCALENDAR
ics
        );
        rewind($obj2);

        $obj3 = <<<ics
BEGIN:VCALENDAR
BEGIN:VEVENT
DTSTART:20111006T120000
DURATION:PT1H
END:VEVENT
END:VCALENDAR
ics;

        $calendarData = [
            1 => [
                'obj1' => [
                    'calendarid'   => 1,
                    'uri'          => 'event1.ics',
                    'calendardata' => $obj1,
                ],
                'obj2' => [
                    'calendarid'   => 1,
                    'uri'          => 'event2.ics',
                    'calendardata' => $obj2
                ],
                'obj3' => [
                    'calendarid'   => 1,
                    'uri'          => 'event3.ics',
                    'calendardata' => $obj3
                ]
            ],
        ];


        $caldavBackend = new Backend\Mock([], $calendarData);

        $calendar = new Calendar($caldavBackend, [
            'id'                                           => 1,
            'uri'                                          => 'calendar',
            'principaluri'                                 => 'principals/user1',
            '{' . Plugin::NS_CALDAV . '}calendar-timezone' => "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nTZID:Europe/Berlin\r\nEND:VTIMEZONE\r\nEND:VCALENDAR",
        ]);

        $this->server = new DAV\Server([$calendar], null, null, function(){});

        $request = new ServerRequest('GET', '/calendar');


        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);
        $this->server->handle($request);

    }

    function testFreeBusyReport() {

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $report = $this->server->xml->parse($reportXML, null, $rootElem);
        $this->plugin->report($rootElem, $report, null);

        $response = $this->server->httpResponse->getResponse();
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/calendar', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(strpos($responseBody, 'BEGIN:VFREEBUSY') !== false);
        $this->assertTrue(strpos($responseBody, '20111005T120000Z/20111005T130000Z') !== false);
        $this->assertTrue(strpos($responseBody, '20111006T100000Z/20111006T110000Z') !== false);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testFreeBusyReportNoTimeRange() {

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
</c:free-busy-query>
XML;

        $report = $this->server->xml->parse($reportXML, null, $rootElem);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\NotImplemented
     */
    function testFreeBusyReportWrongNode() {

        $request = new ServerRequest('REPORT', '/');

        $this->server->handle($request);


        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $report = $this->server->xml->parse($reportXML, null, $rootElem);
        $this->plugin->report($rootElem, $report, null);

    }

    /**
     * @expectedException \Sabre\DAV\Exception
     */
    function testFreeBusyReportNoACLPlugin() {

        $server = new DAV\Server(null, null, null, function(){});
        $plugin = new Plugin();
        $server->addPlugin($plugin);

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $report = $server->xml->parse($reportXML, null, $rootElem);
        $plugin->report($rootElem, $report, null);

    }
}
