<?php

require_once 'Sabre/CalDAV/Backend/Mock.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_CalDAV_ValidateICalTest extends PHPUnit_Framework_TestCase {

    /**
     * @var Sabre_DAV_Server
     */
    protected $server;
    /**
     * @var Sabre_CalDAV_Backend_Mock
     */
    protected $calBackend;

    function setUp() {

        $calendars = array(
            array(
                'id' => 'calendar1',
                'principaluri' => 'principals/admin',
                'uri' => 'calendar1',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet( array('VEVENT','VTODO','VJOURNAL') ),
            ),
            array(
                'id' => 'calendar2',
                'principaluri' => 'principals/admin',
                'uri' => 'calendar2',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet( array('VTODO','VJOURNAL') ),
            )
        );

        $this->calBackend = new Sabre_CalDAV_Backend_Mock($calendars,array());
        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();

        $tree = array(
            new Sabre_CalDAV_CalendarRootNode($principalBackend, $this->calBackend),
        );

        $this->server = new Sabre_DAV_Server($tree);
        $this->server->debugExceptions = true;

        $plugin = new Sabre_CalDAV_Plugin();
        $this->server->addPlugin($plugin);

        $response = new Sabre_HTTP_ResponseMock();
        $this->server->httpResponse = $response;

    }

    function request(Sabre_HTTP_Request $request) {

        $this->server->httpRequest = $request;
        $this->server->exec();

        return $this->server->httpResponse;

    }

    function testCreateFile() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 415 Unsupported Media Type', $response->status);

    }

    function testCreateFileValid() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 201 Created', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);
        $expected = array(
            'uri'          => 'blabla.ics',
            'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
            'calendarid'   => 'calendar1',
        );

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1','blabla.ics'));

    }

    function testCreateFileNoComponents() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 400 Bad request', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFileNoUID() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 400 Bad request', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFileVCard() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));
        $request->setBody("BEGIN:VCARD\r\nEND:VCARD\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 415 Unsupported Media Type', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFile2Components() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nBEGIN:VJOURNAL\r\nUID:foo\r\nEND:VJOURNAL\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 400 Bad request', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFile2UIDS() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:bar\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 400 Bad request', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFileWrongComponent() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VFREEBUSY\r\nUID:foo\r\nEND:VFREEBUSY\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 400 Bad request', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testUpdateFile() {

        $this->calBackend->createCalendarObject('calendar1','blabla.ics','foo');
        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 415 Unsupported Media Type', $response->status);

    }

    function testUpdateFileParsableBody() {

        $this->calBackend->createCalendarObject('calendar1','blabla.ics','foo');
        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));
        $body = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $request->setBody($body);

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 204 No Content', $response->status);

        $expected = array(
            'uri'          => 'blabla.ics',
            'calendardata' => $body,
            'calendarid'   => 'calendar1',
        );

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1','blabla.ics'));

    }

    function testCreateFileInvalidComponent() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar2/blabla.ics',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testUpdateFileInvalidComponent() {

        $this->calBackend->createCalendarObject('calendar2','blabla.ics','foo');
        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar2/blabla.ics',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }
}
