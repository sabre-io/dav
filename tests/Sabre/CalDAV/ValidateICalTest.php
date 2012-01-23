<?php

require_once 'Sabre/CalDAV/Backend/Mock.php';
require_once 'Sabre/DAVACL/MockPrincipalBackend.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_CalDAV_ValidateICalTest extends PHPUnit_Framework_TestCase {

    protected $server;
    protected $calBackend;

    function setUp() {

        $calendars = array(
            array(
                'id' => 'calendar1',
                'principaluri' => 'principals/admin',
                'uri' => 'calendar1',
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

    function testCreateFileParsableBody() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/calendars/admin/calendar1/blabla.ics',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 201 Created', $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

        $expected = array(
            'uri'          => 'blabla.ics',
            'calendardata' => "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n",
            'calendarid'   => 'calendar1',
        );

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1','blabla.ics'));

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
        $request->setBody("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 204 No Content', $response->status);

        $expected = array(
            'uri'          => 'blabla.ics',
            'calendardata' => "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n",
            'calendarid'   => 'calendar1',
        );

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1','blabla.ics'));

    }
}

?>
