<?php

require_once 'Sabre/DAVACL/MockPrincipalBackend.php';
require_once 'Sabre/CalDAV/Backend/Mock.php';
require_once 'Sabre/DAV/Auth/MockBackend.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_CalDAV_FreeBusyRequestTest extends PHPUnit_Framework_TestCase {

    protected $plugin;
    protected $server;
    protected $aclPlugin;
    protected $request;
    protected $authPlugin;

    function setUp() {

        $calendars = array(
            array(
                'principaluri' => 'principals/user2',
                'id'           => 1,
                'uri'          => 'calendar1',
            ),
        );
        $calendarobjects = array(
            1 => array( '1.ics' => array(
                'uri' => '1.ics',
                'calendardata' => 'BEGIN:VCALENDAR
BEGIN:VEVENT
DTSTART:20110101T130000
DURATION:PT1H
END:VEVENT
END:VCALENDAR',
                'calendarid' => 1,
            ))

        );

        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();
        $caldavBackend = new Sabre_CalDAV_Backend_Mock($calendars, $calendarobjects);

        $tree = array(
            new Sabre_DAVACL_PrincipalCollection($principalBackend),
            new Sabre_CalDAV_CalendarRootNode($principalBackend, $caldavBackend),
        );

        $this->request = new Sabre_HTTP_Request(array(
            'CONTENT_TYPE' => 'text/calendar',
        ));
        $this->response = new Sabre_HTTP_ResponseMock();

        $this->server = new Sabre_DAV_Server($tree);
        $this->server->httpRequest = $this->request;
        $this->server->httpResponse = $this->response;

        $this->aclPlugin = new Sabre_DAVACL_Plugin();
        $this->server->addPlugin($this->aclPlugin);

        $authBackend = new Sabre_DAV_Auth_MockBackend();
        $authBackend->setCurrentUser('user1');
        $this->authPlugin = new Sabre_DAV_Auth_Plugin($authBackend,'SabreDAV');
        $this->server->addPlugin($this->authPlugin);

        $this->plugin = new Sabre_CalDAV_Plugin();
        $this->server->addPlugin($this->plugin);

    }

    function testWrongMethod() {

        $this->assertNull(
            $this->plugin->unknownMethod('PUT','calendars/user1/outbox')
        );

    }

    function testWrongContentType() {

        $this->server->httpRequest = new Sabre_HTTP_Request(array(
            'CONTENT_TYPE' => 'text/plain',
        ));

        $this->assertNull(
            $this->plugin->unknownMethod('POST','calendars/user1/outbox')
        );

    }

    function testNotFound() {

        $this->assertNull(
            $this->plugin->unknownMethod('POST','calendars/user1/blabla')
        );

    }

    function testNotOutbox() {

        $this->assertNull(
            $this->plugin->unknownMethod('POST','calendars/user1/inbox')
        );

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testNoItipMethod() {

        $body = <<<ICS
BEGIN:VCALENDAR
BEGIN:VFREEBUSY
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','calendars/user1/outbox');

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testNoVFreeBusy() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VEVENT
END:VEVENT
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','calendars/user1/outbox');

    }

    /**
     * @expectedException Sabre_DAV_Exception_Forbidden
     */
    function testIncorrectOrganizer() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:john@wayne.org
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','calendars/user1/outbox');

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testNoAttendees() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:user1.sabredav@sabredav.org
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','calendars/user1/outbox');

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testNoDTStart() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','calendars/user1/outbox');

    }

    function testSucceed() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
ATTENDEE:mailto:user3.sabredav@sabredav.org
DTSTART:20110101T080000Z
DTEND:20110101T180000Z
END:VFREEBUSY
END:VCALENDAR
ICS;

        // Lazily making the current principal an admin.
        $this->aclPlugin->adminPrincipals[] = 'principals/user1';

        $this->request->setBody($body);
        $this->assertFalse($this->plugin->unknownMethod('POST','calendars/user1/outbox'));

        $this->assertEquals('HTTP/1.1 200 OK' , $this->response->status);
        $this->assertEquals(array(
            'Content-Type' => 'application/xml',
        ), $this->response->headers);

        $strings = array(
            '<d:href>mailto:user2.sabredav@sabredav.org</d:href>',
            '<d:href>mailto:user3.sabredav@sabredav.org</d:href>',
            '<cal:request-status>2.0;Success</cal:request-status>',
            '<cal:request-status>3.7;Could not find principal</cal:request-status>',
            'FREEBUSY;FBTYPE=BUSY:20110101T130000Z/20110101T140000Z',
        );

        foreach($strings as $string) {
            $this->assertTrue(
                strpos($this->response->body, $string)!==false,
                'The response body did not contain: ' . $string .'Full response: ' . $this->response->body
            );
        }


    }

    function testNoPrivilege() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
DTSTART:20110101T080000Z
DTEND:20110101T180000Z
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->assertFalse($this->plugin->unknownMethod('POST','calendars/user1/outbox'));

        $this->assertEquals('HTTP/1.1 200 OK' , $this->response->status);
        $this->assertEquals(array(
            'Content-Type' => 'application/xml',
        ), $this->response->headers);

        $strings = array(
            '<d:href>mailto:user2.sabredav@sabredav.org</d:href>',
            '<cal:request-status>3.7;No calendar-home-set property found</cal:request-status>',
        );

        foreach($strings as $string) {
            $this->assertTrue(
                strpos($this->response->body, $string)!==false,
                'The response body did not contain: ' . $string .'Full response: ' . $this->response->body
            );
        }


    }

}
