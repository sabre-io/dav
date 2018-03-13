<?php declare (strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\DAV;
use Sabre\DAV\Psr7RequestWrapper;
use Sabre\DAVACL;
use GuzzleHttp\Psr7\ServerRequest;
class FreeBusyRequestTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Plugin
     */
    protected $plugin;
    /**
     * @var DAV\Server
     */
    protected $server;
    protected $aclPlugin;
    protected $request;
    protected $authPlugin;
    protected $caldavBackend;

    function setUp() {

        $caldavNS = '{' . CalDAV\Plugin::NS_CALDAV . '}';
        $calendars = [
            [
                'principaluri'                  => 'principals/user2',
                'id'                            => 1,
                'uri'                           => 'calendar1',
                $caldavNS . 'calendar-timezone' => "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nTZID:Europe/Berlin\r\nEND:VTIMEZONE\r\nEND:VCALENDAR",
            ],
            [
                'principaluri'                         => 'principals/user2',
                'id'                                   => 2,
                'uri'                                  => 'calendar2',
                $caldavNS . 'schedule-calendar-transp' => new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT),
            ],
        ];
        $calendarobjects = [
            1 => ['1.ics' => [
                'uri'          => '1.ics',
                'calendardata' => 'BEGIN:VCALENDAR
BEGIN:VEVENT
DTSTART:20110101T130000
DURATION:PT1H
END:VEVENT
END:VCALENDAR',
                'calendarid' => 1,
            ]],
            2 => ['2.ics' => [
                'uri'          => '2.ics',
                'calendardata' => 'BEGIN:VCALENDAR
BEGIN:VEVENT
DTSTART:20110101T080000
DURATION:PT1H
END:VEVENT
END:VCALENDAR',
                'calendarid' => 2,
            ]]

        ];

        $principalBackend = new DAVACL\PrincipalBackend\Mock();
        $this->caldavBackend = new CalDAV\Backend\MockScheduling($calendars, $calendarobjects);

        $tree = [
            new DAVACL\PrincipalCollection($principalBackend),
            new CalDAV\CalendarRoot($principalBackend, $this->caldavBackend),
        ];

        $this->request = new ServerRequest('GET', '/', [
            'Content-Type' => 'text/calendar',
        ]);

        $this->server = new DAV\Server($tree, null, null, function(){});

        $this->aclPlugin = new DAVACL\Plugin();
        $this->aclPlugin->allowUnauthenticatedAccess = false;
        $this->server->addPlugin($this->aclPlugin);

        $authBackend = new DAV\Auth\Backend\Mock();
        $authBackend->setPrincipal('principals/user1');
        $this->authPlugin = new DAV\Auth\Plugin($authBackend);
        // Forcing authentication to work.
        $this->authPlugin->beforeMethod(new DAV\Psr7RequestWrapper($this->request), $this->server->httpResponse);
        $this->server->addPlugin($this->authPlugin);

        // CalDAV plugin
        $this->plugin = new CalDAV\Plugin();
        $this->server->addPlugin($this->plugin);

        // Scheduling plugin
        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);

    }

    function testWrongContentType() {

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/plain']
        );

        $this->assertNull(
            $this->plugin->httpPost(new DAV\Psr7RequestWrapper($request), $this->server->httpResponse)
        );

    }

    function testNotFound() {

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/blabla',
            ['Content-Type' => 'text/calendar']
        );

        $this->assertNull(
            $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse)
        );

    }

    function testNotOutbox() {

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/inbox',
            ['Content-Type' => 'text/calendar']
        );

        $this->assertNull(
            $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse)
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testNoItipMethod() {



        $body = <<<ICS
BEGIN:VCALENDAR
BEGIN:VFREEBUSY
END:VFREEBUSY
END:VCALENDAR
ICS;

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );
        $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\NotImplemented
     */
    function testNoVFreeBusy() {



        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VEVENT
END:VEVENT
END:VCALENDAR
ICS;

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );
        $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testIncorrectOrganizer() {

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:john@wayne.org
END:VFREEBUSY
END:VCALENDAR
ICS
        );

        $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
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
        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );



        $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
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

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );
        $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse);

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

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );

        // Lazily making the current principal an admin.
        $this->aclPlugin->adminPrincipals[] = 'principals/user1';

        $this->assertFalse(
            $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse)
        );
        $response = $this->server->handle(new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        ));
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $responseBody);
        $this->assertEquals([
            'Content-Type' => ['application/xml'],

        ], $response->getHeaders());

        $strings = [
            '<d:href>mailto:user2.sabredav@sabredav.org</d:href>',
            '<d:href>mailto:user3.sabredav@sabredav.org</d:href>',
            '<cal:request-status>2.0;Success</cal:request-status>',
            '<cal:request-status>3.7;Could not find principal</cal:request-status>',
            'FREEBUSY:20110101T120000Z/20110101T130000Z',
        ];

        foreach ($strings as $string) {
            $this->assertTrue(
                strpos($responseBody, $string) !== false,
                'The response body did not contain: ' . $string . 'Full response: ' . $responseBody
            );
        }

        $this->assertTrue(
            strpos($responseBody, 'FREEBUSY;FBTYPE=BUSY:20110101T080000Z/20110101T090000Z') == false,
            'The response body did contain free busy info from a transparent calendar.'
        );

    }

    /**
     * Testing if the freebusy request still works, even if there are no
     * calendars in the target users' account.
     */
    function testSucceedNoCalendars() {

        // Deleting calendars
        $this->caldavBackend->deleteCalendar(1);
        $this->caldavBackend->deleteCalendar(2);


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

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );


        // Lazily making the current principal an admin.
        $this->aclPlugin->adminPrincipals[] = 'principals/user1';

        $this->assertFalse(
            $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse)
        );

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );
        $response = $this->server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $responseBody);
        $this->assertEquals([
            'Content-Type' => ['application/xml'],

        ], $response->getHeaders());

        $strings = [
            '<d:href>mailto:user2.sabredav@sabredav.org</d:href>',
            '<cal:request-status>2.0;Success</cal:request-status>',
        ];

        foreach ($strings as $string) {
            $this->assertTrue(
                strpos($responseBody, $string) !== false,
                'The response body did not contain: ' . $string . 'Full response: ' . $responseBody
            );
        }

    }

    function testNoCalendarHomeFound() {
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

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );



        // Lazily making the current principal an admin.
        $this->aclPlugin->adminPrincipals[] = 'principals/user1';

        // Removing the calendar home
        $this->server->on('propFind', function(DAV\PropFind $propFind) {

            $propFind->set('{' . Plugin::NS_CALDAV . '}calendar-home-set', null, 403);

        });

        $this->assertFalse(
            $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse)
        );


        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );
        $response = $this->server->handle($request);
        $responseBody = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'Content-Type' => ['application/xml'],

        ], $response->getHeaders());

        $strings = [
            '<d:href>mailto:user2.sabredav@sabredav.org</d:href>',
            '<cal:request-status>3.7;No calendar-home-set property found</cal:request-status>',
        ];

        foreach ($strings as $string) {
            $this->assertTrue(
                strpos($responseBody, $string) !== false,
                'The response body did not contain: ' . $string . 'Full response: ' . $responseBody
            );
        }

    }

    function testNoInboxFound() {


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

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );

        // Lazily making the current principal an admin.
        $this->aclPlugin->adminPrincipals[] = 'principals/user1';

        // Removing the inbox
        $this->server->on('propFind', function(DAV\PropFind $propFind) {

            $propFind->set('{' . Plugin::NS_CALDAV . '}schedule-inbox-URL', null, 403);

        });

        $this->assertFalse(
            $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse)
        );

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );

        $response = $this->server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $responseBody);
        $this->assertEquals([
            'Content-Type' => ['application/xml'],

        ], $response->getHeaders());

        $strings = [
            '<d:href>mailto:user2.sabredav@sabredav.org</d:href>',
            '<cal:request-status>3.7;No schedule-inbox-URL property found</cal:request-status>',
        ];

        foreach ($strings as $string) {
            $this->assertTrue(
                strpos($responseBody, $string) !== false,
                'The response body did not contain: ' . $string . 'Full response: ' .$responseBody
            );
        }

    }

    function testSucceedUseVAVAILABILITY() {



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

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        );

        // Lazily making the current principal an admin.
        $this->aclPlugin->adminPrincipals[] = 'principals/user1';

        // Adding VAVAILABILITY manually
        $this->server->on('propFind', function(DAV\PropFind $propFind) {

            $propFind->handle('{' . Plugin::NS_CALDAV . '}calendar-availability', function() {

                $avail = <<<ICS
BEGIN:VCALENDAR
BEGIN:VAVAILABILITY
DTSTART:20110101T000000Z
DTEND:20110102T000000Z
BEGIN:AVAILABLE
DTSTART:20110101T090000Z
DTEND:20110101T170000Z
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR
ICS;
                return $avail;

            });

        });

        $this->assertFalse(
            $this->plugin->httpPost(new Psr7RequestWrapper($request), $this->server->httpResponse)
        );

        $response = $this->server->handle(new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            ['Content-Type' => 'text/calendar'],
            $body
        ));
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $responseBody);
        $this->assertEquals([
            'Content-Type' => ['application/xml'],
        ], $response->getHeaders());

        $strings = [
            '<d:href>mailto:user2.sabredav@sabredav.org</d:href>',
            '<cal:request-status>2.0;Success</cal:request-status>',
            'FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20110101T080000Z/20110101T090000Z',
            'FREEBUSY:20110101T120000Z/20110101T130000Z',
            'FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20110101T170000Z/20110101T180000Z',
        ];

        foreach ($strings as $string) {
            $this->assertTrue(
                strpos($responseBody, $string) !== false,
                'The response body did not contain: ' . $string . 'Full response: ' . $responseBody
            );
        }

    }

}
