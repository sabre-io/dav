<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class FreeBusyRequestTest extends \PHPUnit_Framework_TestCase {

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

        $principalBackend = new DAVACL\PrincipalBackend\Mock();
        $caldavBackend = new Backend\Mock($calendars, $calendarobjects);

        $tree = array(
            new DAVACL\PrincipalCollection($principalBackend),
            new CalendarRootNode($principalBackend, $caldavBackend),
        );

        $this->request = HTTP\Sapi::createFromServerArray([
            'CONTENT_TYPE' => 'text/calendar',
        ]);
        $this->response = new HTTP\ResponseMock();

        $this->server = new DAV\Server($tree);
        $this->server->httpRequest = $this->request;
        $this->server->httpResponse = $this->response;

        $this->aclPlugin = new DAVACL\Plugin();
        $this->server->addPlugin($this->aclPlugin);

        $authBackend = new DAV\Auth\Backend\Mock();
        $authBackend->setCurrentUser('user1');
        $this->authPlugin = new DAV\Auth\Plugin($authBackend,'SabreDAV');
        $this->server->addPlugin($this->authPlugin);

        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);

    }

    function testWrongContentType() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/plain',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
        ));

        $this->assertNull(
            $this->plugin->httpPost($this->server->httpRequest, $this->server->httpResponse)
        );

    }

    function testNotFound() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/calendar',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/blabla',
        ));

        $this->assertNull(
            $this->plugin->httpPost($this->server->httpRequest, $this->server->httpResponse)
        );

    }

    function testNotOutbox() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/calendar',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/inbox',
        ));

        $this->assertNull(
            $this->plugin->httpPost($this->server->httpRequest, $this->server->httpResponse)
        );

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testNoItipMethod() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/calendar',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
        ));

        $body = <<<ICS
BEGIN:VCALENDAR
BEGIN:VFREEBUSY
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->server->httpRequest->setBody($body);
        $this->plugin->httpPost($this->server->httpRequest, $this->server->httpResponse);

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testNoVFreeBusy() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/calendar',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
        ));

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VEVENT
END:VEVENT
END:VCALENDAR
ICS;

        $this->server->httpRequest->setBody($body);
        $this->plugin->httpPost($this->server->httpRequest, $this->server->httpResponse);

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testIncorrectOrganizer() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/calendar',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
        ));

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:john@wayne.org
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->server->httpRequest->setBody($body);
        $this->plugin->httpPost($this->server->httpRequest, $this->server->httpResponse);

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testNoAttendees() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/calendar',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
        ));

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:user1.sabredav@sabredav.org
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->server->httpRequest->setBody($body);
        $this->plugin->httpPost($this->server->httpRequest, $this->server->httpResponse);

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testNoDTStart() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/calendar',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
        ));

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->server->httpRequest->setBody($body);
        $this->plugin->httpPost($this->server->httpRequest, $this->server->httpResponse);

    }

    function testSucceed() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/calendar',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
        ));

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

        $this->server->httpRequest->setBody($body);

        // Lazily making the current principal an admin.
        $this->aclPlugin->adminPrincipals[] = 'principals/user1';

        $this->assertFalse(
            $this->plugin->httpPost($this->server->httpRequest, $this->response)
        );

        $this->assertEquals(200, $this->response->status);
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

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'CONTENT_TYPE' => 'text/calendar',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
        ));

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

        $this->server->httpRequest->setBody($body);

        $this->assertFalse(
            $this->plugin->httpPost($this->server->httpRequest, $this->response)
        );

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals([
            'Content-Type' => 'application/xml',
        ], $this->response->headers);

        $strings = [
            '<d:href>mailto:user2.sabredav@sabredav.org</d:href>',
            '<cal:request-status>3.7;No calendar-home-set property found</cal:request-status>',
        ];

        foreach($strings as $string) {
            $this->assertTrue(
                strpos($this->response->body, $string)!==false,
                'The response body did not contain: ' . $string .'Full response: ' . $this->response->body
            );
        }


    }

}
