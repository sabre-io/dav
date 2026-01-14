<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\HTTP;

class FreeBusyReportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Plugin
     */
    protected $plugin;
    /**
     * @var DAV\Server
     */
    protected $server;

    public function setup(): void
    {
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
                    'calendarid' => 1,
                    'uri' => 'event1.ics',
                    'calendardata' => $obj1,
                ],
                'obj2' => [
                    'calendarid' => 1,
                    'uri' => 'event2.ics',
                    'calendardata' => $obj2,
                ],
                'obj3' => [
                    'calendarid' => 1,
                    'uri' => 'event3.ics',
                    'calendardata' => $obj3,
                ],
            ],
        ];

        $caldavBackend = new Backend\Mock([], $calendarData);

        $calendar = new Calendar($caldavBackend, [
            'id' => 1,
            'uri' => 'calendar',
            'principaluri' => 'principals/user1',
            '{'.Plugin::NS_CALDAV.'}calendar-timezone' => "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nTZID:Europe/Berlin\r\nEND:VTIMEZONE\r\nEND:VCALENDAR",
        ]);

        $this->server = new DAV\Server([$calendar]);

        $request = new HTTP\Request('GET', '/calendar');
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();

        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);
    }

    public function testFreeBusyReport()
    {
        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $report = $this->server->xml->parse($reportXML, null, $rootElem);
        $this->plugin->report($rootElem, $report, null);

        self::assertEquals(200, $this->server->httpResponse->status);
        self::assertEquals('text/calendar', $this->server->httpResponse->getHeader('Content-Type'));
        self::assertTrue(false !== strpos($this->server->httpResponse->body, 'BEGIN:VFREEBUSY'));
        self::assertTrue(false !== strpos($this->server->httpResponse->body, '20111005T120000Z/20111005T130000Z'));
        self::assertTrue(false !== strpos($this->server->httpResponse->body, '20111006T100000Z/20111006T110000Z'));
    }

    public function testFreeBusyReportNoTimeRange()
    {
        $this->expectException(\Sabre\DAV\Exception\BadRequest::class);
        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
</c:free-busy-query>
XML;

        $report = $this->server->xml->parse($reportXML, null, $rootElem);
    }

    public function testFreeBusyReportWrongNode()
    {
        $this->expectException(\Sabre\DAV\Exception\NotImplemented::class);
        $request = new HTTP\Request('REPORT', '/');
        $this->server->httpRequest = $request;

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $report = $this->server->xml->parse($reportXML, null, $rootElem);
        $this->plugin->report($rootElem, $report, null);
    }

    public function testFreeBusyReportNoACLPlugin()
    {
        $this->expectException(\Sabre\DAV\Exception::class);
        $this->server = new DAV\Server();
        $this->server->httpRequest = new HTTP\Request('REPORT', '/');
        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);

        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="20111001T000000Z" end="20111101T000000Z" />
</c:free-busy-query>
XML;

        $report = $this->server->xml->parse($reportXML, null, $rootElem);
        $this->plugin->report($rootElem, $report, null);
    }

    public function testFreeBusyReportInvalidTimeRange()
    {
        $reportXML = <<<XML
<?xml version="1.0"?>
<c:free-busy-query xmlns:c="urn:ietf:params:xml:ns:caldav">
    <c:time-range start="19900101" end="20400101"/>
</c:free-busy-query>
XML;
        $this->expectException(\Sabre\DAV\Exception\BadRequest::class);
        $this->expectExceptionMessage('The supplied iCalendar datetime value is incorrect: 19900101');

        $report = $this->server->xml->parse($reportXML, null, $rootElem);
        $this->plugin->report($rootElem, $report, null);
    }
}
