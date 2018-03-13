<?php declare (strict_types=1);

namespace Sabre\CalDAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\CalDAV\Backend\Mock;
use Sabre\DAV;
use Sabre\DAVACL;

class ValidateICalTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DAV\Server
     */
    protected $server;
    /**
     * @var Mock
     */
    protected $calBackend;

    function setUp() {

        $calendars = [
            [
                'id'                                                              => 'calendar1',
                'principaluri'                                                    => 'principals/admin',
                'uri'                                                             => 'calendar1',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Xml\Property\SupportedCalendarComponentSet(['VEVENT', 'VTODO', 'VJOURNAL']),
            ],
            [
                'id'                                                              => 'calendar2',
                'principaluri'                                                    => 'principals/admin',
                'uri'                                                             => 'calendar2',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Xml\Property\SupportedCalendarComponentSet(['VTODO', 'VJOURNAL']),
            ]
        ];

        $this->calBackend = new Backend\Mock($calendars, []);
        $principalBackend = new DAVACL\PrincipalBackend\Mock();

        $tree = [
            new CalendarRoot($principalBackend, $this->calBackend),
        ];

        $this->server = new DAV\Server($tree, null, null, function(){});
        $this->server->debugExceptions = true;

        $plugin = new Plugin();
        $this->server->addPlugin($plugin);

    }

    function testCreateFile() {

        
        $request = new ServerRequest('PUT', '/calendars/admin/calendar1/blabla.ics');

        $response = $this->server->handle($request);

        $this->assertEquals(415, $response->getStatusCode());

    }

    function testCreateFileValid() {



        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
BEGIN:VEVENT
UID:foo
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $request = new ServerRequest(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            ['Prefer' => 'handling=strict'],
            $ics
        );

        $response = $this->server->handle($request);

        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(201, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $responseBody);
        $this->assertEquals([

            'Content-Length'  => ['0'],
            'ETag'            => ['"' . md5($ics) . '"'],
        ], $response->getHeaders());

        $expected = [
            'uri'          => 'blabla.ics',
            'calendardata' => $ics,
            'calendarid'   => 'calendar1',
            'lastmodified' => null,
        ];

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1', 'blabla.ics'));

    }

    function testCreateFileNoVersion() {



        $ics = <<<ICS
BEGIN:VCALENDAR
PRODID:foo
BEGIN:VEVENT
UID:foo
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $request = new ServerRequest(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            ['Prefer' => 'handling=strict'],
            $ics
        );

        $response = $this->server->handle($request);

        $this->assertEquals(415, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    function testCreateFileNoVersionFixed() {

        $ics = <<<ICS
BEGIN:VCALENDAR
PRODID:foo
BEGIN:VEVENT
UID:foo
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $request = new ServerRequest(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            ['Prefer' => 'handling=lenient'],
            $ics
        );

        $response = $this->server->handle($request);

        $this->assertEquals(201, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());
        $this->assertEquals([
            'Content-Length'   => ['0'],
            'X-Sabre-Ew-Gross' => ['iCalendar validation warning: VERSION MUST appear exactly once in a VCALENDAR component'],
        ], $response->getHeaders());

        $ics = <<<ICS
BEGIN:VCALENDAR\r
VERSION:2.0\r
PRODID:foo\r
BEGIN:VEVENT\r
UID:foo\r
DTSTAMP:20160406T052348Z\r
DTSTART:20160706T140000Z\r
END:VEVENT\r
END:VCALENDAR\r

ICS;

        $expected = [
            'uri'          => 'blabla.ics',
            'calendardata' => $ics,
            'calendarid'   => 'calendar1',
            'lastmodified' => null,
        ];

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1', 'blabla.ics'));

    }

    function testCreateFileNoComponents() {


        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
END:VCALENDAR
ICS;
        $request = new ServerRequest(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            ['Prefer' => 'handling=strict'],
            $ics
        );

        $response = $this->server->handle($request);
        $this->assertEquals(403, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    function testCreateFileNoUID() {

        $request = new ServerRequest('PUT', '/calendars/admin/calendar1/blabla.ics', [],
            "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->server->handle($request);

        $this->assertEquals(415, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    function testCreateFileVCard() {

        $request = new ServerRequest('PUT', '/calendars/admin/calendar1/blabla.ics', [], "BEGIN:VCARD\r\nEND:VCARD\r\n");

        $response = $this->server->handle($request);

        $this->assertEquals(415, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    function testCreateFile2Components() {

        $request = new ServerRequest('PUT', '/calendars/admin/calendar1/blabla.ics', [],
            "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nBEGIN:VJOURNAL\r\nUID:foo\r\nEND:VJOURNAL\r\nEND:VCALENDAR\r\n"
        );

        $response = $this->server->handle($request);

        $this->assertEquals(415, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    function testCreateFile2UIDS() {

        $request = new ServerRequest('PUT', '/calendars/admin/calendar1/blabla.ics', [],
        "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:bar\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->server->handle($request);

        $this->assertEquals(415, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    function testCreateFileWrongComponent() {

        $request = new ServerRequest('PUT', '/calendars/admin/calendar1/blabla.ics', [],
            "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VFREEBUSY\r\nUID:foo\r\nEND:VFREEBUSY\r\nEND:VCALENDAR\r\n");

        $response = $this->server->handle($request);

        $this->assertEquals(403, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    function testUpdateFile() {

        $this->calBackend->createCalendarObject('calendar1', 'blabla.ics', 'foo');
        $request = new ServerRequest('PUT', '/calendars/admin/calendar1/blabla.ics');

        $response = $this->server->handle($request);

        $this->assertEquals(415, $response->getStatusCode(), $response->getBody()->getContents());

    }

    function testUpdateFileParsableBody() {

        $this->calBackend->createCalendarObject('calendar1', 'blabla.ics', 'foo');
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
BEGIN:VEVENT
UID:foo
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;
        $request = new ServerRequest(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            [],
            $ics
        );

        $response = $this->server->handle($request);

        $this->assertEquals(204, $response->getStatusCode());

        $expected = [
            'uri'          => 'blabla.ics',
            'calendardata' => $ics,
            'calendarid'   => 'calendar1',
            'lastmodified' => null,
        ];

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1', 'blabla.ics'));

    }

    function testCreateFileInvalidComponent() {

        $request = new ServerRequest('PUT', '/calendars/admin/calendar2/blabla.ics', [],
            "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->server->handle($request);

        $this->assertEquals(403, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    function testUpdateFileInvalidComponent() {

        $this->calBackend->createCalendarObject('calendar2', 'blabla.ics', 'foo');
        $request = new ServerRequest('PUT', '/calendars/admin/calendar2/blabla.ics', [],
            "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->server->handle($request);

        $this->assertEquals(403, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * What we are testing here, is if we send in a latin1 character, the
     * server should automatically transform this into UTF-8.
     *
     * More importantly. If any transformation happens, the etag must no longer
     * be returned by the server.
     */
    function testCreateFileModified() {


        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
BEGIN:VEVENT
UID:foo
SUMMARY:Meeting in M\xfcnster
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $request = new ServerRequest(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            [],
            $ics
        );
        $response = $this->server->handle($request);

        $this->assertEquals(201, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());
        $this->assertEmpty($response->getHeader('ETag'));

    }
}
