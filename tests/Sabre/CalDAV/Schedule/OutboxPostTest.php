<?php declare (strict_types=1);

namespace Sabre\CalDAV\Schedule;

use GuzzleHttp\Psr7\ServerRequest;

class OutboxPostTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $setupACL = true;
    protected $autoLogin = 'user1';
    protected $setupCalDAVScheduling = true;

    function testPostPassThruNotFound() {

        $request = new ServerRequest( 'POST','/notfound', ['Content-Type' => 'text/calendar']);
        $this->request($request, 501);

    }

    function testPostPassThruNotTextCalendar() {

        $request = new ServerRequest('POST','/calendars/user1/outbox');
        $this->request($request, 501);

    }

    function testPostPassThruNoOutBox() {

        $request = new ServerRequest('POST', '/calendars', ['Content-Type' => 'text/calendar']);
        $this->request($request, 501);
    }

    function testInvalidIcalBody() {

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            [
                'Originator' => 'mailto:user1.sabredav@sabredav.org',
                'Recipient' => 'mailto:user2@example.org',
                'Content-Type' => 'text/calendar',
            ],
            'foo'
        );
        $this->request($request, 400);

    }

    function testNoVEVENT() {

        $body = [
            'BEGIN:VCALENDAR',
            'BEGIN:VTIMEZONE',
            'END:VTIMEZONE',
            'END:VCALENDAR',
        ];
        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            [
                'Originator'   => 'mailto:user1.sabredav@sabredav.org',
                'Recipient'    => 'mailto:user2@example.org',
                'Content-Type' => 'text/calendar',
            ],
            implode("\r\n", $body)
        );



            $this->request($request, 400);
    }

    function testNoMETHOD() {

        $body = [
            'BEGIN:VCALENDAR',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        ];
        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            [

            'HTTP_ORIGINATOR'   => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'    => 'mailto:user2@example.org',
            'Content-Type' => 'text/calendar',
            ],
            implode("\r\n", $body)
        );


        $this->request($request, 400);

    }

    function testUnsupportedMethod() {

        $body = [
            'BEGIN:VCALENDAR',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        ];


        $request = new ServerRequest(
            'POST',
            '/calendars/user1/outbox',
            [

                'HTTP_ORIGINATOR'   => 'mailto:user1.sabredav@sabredav.org',
                'HTTP_RECIPIENT'    => 'mailto:user2@example.org',
                'Content-Type' => 'text/calendar',
            ],
            implode("\r\n", $body)
        );


        $this->request($request, 501);

    }

}
