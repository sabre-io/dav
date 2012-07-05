<?php

require_once 'Sabre/DAVServerTest.php';
require_once 'Sabre/CalDAV/Schedule/IMip/Mock.php';

class Sabre_CalDAV_OutboxPostTest extends Sabre_DAVServerTest {

    protected $setupCalDAV = true;

    function testPostPassThruNotFound() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/notfound',
        ));

        $this->assertHTTPStatus(501, $req);

    }

    function testPostPassThruNoOutBox() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars',
        ));

        $this->assertHTTPStatus(501, $req);

    }

    function testNoOriginator() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/admin/outbox',
        ));

        $this->assertHTTPStatus(400, $req);

    }

    function testNoRecipient() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'mailto:orig@example.org',
        ));

        $this->assertHTTPStatus(400, $req);

    }

    function testBadOriginator() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'nomailto:orig@example.org',
            'HTTP_RECIPIENT'  => 'mailto:user1@example.org',
        ));

        $this->assertHTTPStatus(400, $req);

    }

    function testBadRecipient() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'mailto:orig@example.org',
            'HTTP_RECIPIENT'  => 'http://user1@example.org, mailto:user2@example.org',
        ));

        $this->assertHTTPStatus(400, $req);

    }

    function testIncorrectOriginator() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'mailto:orig@example.org',
            'HTTP_RECIPIENT'  => 'mailto:user1@example.org, mailto:user2@example.org',
        ));

        $this->assertHTTPStatus(403, $req);

    }

    function testInvalidIcalBody() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
        ));
        $req->setBody('foo');

        $this->assertHTTPStatus(400, $req);

    }

    function testNoVEVENT() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'BEGIN:VTIMEZONE',
            'END:VTIMEZONE',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(400, $req);

    }

    function testNoMETHOD() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(400, $req);

    }

    function testUnsupportedMethod() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(501, $req);

    }

    function testNoIMIPHandler() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));


        $response = $this->request($req);
        $this->assertEquals('HTTP/1.1 200 OK', $response->status);
        $this->assertEquals(array(
            'Content-Type' => 'application/xml',
        ), $response->headers);

        // Lazily checking the body for a few expected values.
        $this->assertTrue(strpos($response->body, '5.2;')!==false);
        $this->assertTrue(strpos($response->body,'user2@example.org')!==false);


    }

    function testSuccessRequest() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Sabre_CalDAV_Schedule_IMip_Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);

        $response = $this->request($req);
        $this->assertEquals('HTTP/1.1 200 OK', $response->status);
        $this->assertEquals(array(
            'Content-Type' => 'application/xml',
        ), $response->headers);

        // Lazily checking the body for a few expected values.
        $this->assertTrue(strpos($response->body, '2.0;')!==false);
        $this->assertTrue(strpos($response->body,'user2@example.org')!==false);

        $this->assertEquals(array(
            array(
                'to' => 'user2@example.org',
                'subject' => 'Invitation for: An invitation',
                'body' => implode("\r\n", $body) . "\r\n",
                'headers' => array(
                    'Reply-To: user1.sabredav@sabredav.org',
                    'From: server@example.org',
                    'Content-Type: text/calendar; method=REQUEST; charset=utf-8',
                    'X-Sabre-Version: ' . Sabre_DAV_Version::VERSION . '-' . Sabre_DAV_Version::STABILITY,
                ),
           )
        ), $handler->getSentEmails());

    }

    function testSuccessRequestUpperCased() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'MAILTO:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'MAILTO:user2@example.org',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Sabre_CalDAV_Schedule_IMip_Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);

        $response = $this->request($req);
        $this->assertEquals('HTTP/1.1 200 OK', $response->status);
        $this->assertEquals(array(
            'Content-Type' => 'application/xml',
        ), $response->headers);

        // Lazily checking the body for a few expected values.
        $this->assertTrue(strpos($response->body, '2.0;')!==false);
        $this->assertTrue(strpos($response->body,'user2@example.org')!==false);

        $this->assertEquals(array(
            array(
                'to' => 'user2@example.org',
                'subject' => 'Invitation for: An invitation',
                'body' => implode("\r\n", $body) . "\r\n",
                'headers' => array(
                    'Reply-To: user1.sabredav@sabredav.org',
                    'From: server@example.org',
                    'Content-Type: text/calendar; method=REQUEST; charset=utf-8',
                    'X-Sabre-Version: ' . Sabre_DAV_Version::VERSION . '-' . Sabre_DAV_Version::STABILITY,
                ),
           )
        ), $handler->getSentEmails());

    }

    function testSuccessReply() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REPLY',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Sabre_CalDAV_Schedule_IMip_Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);
        $this->assertHTTPStatus(200, $req);

        $this->assertEquals(array(
            array(
                'to' => 'user2@example.org',
                'subject' => 'Response for: An invitation',
                'body' => implode("\r\n", $body) . "\r\n",
                'headers' => array(
                    'Reply-To: user1.sabredav@sabredav.org',
                    'From: server@example.org',
                    'Content-Type: text/calendar; method=REPLY; charset=utf-8',
                    'X-Sabre-Version: ' . Sabre_DAV_Version::VERSION . '-' . Sabre_DAV_Version::STABILITY,
                ),
           )
        ), $handler->getSentEmails());

    }

    function testSuccessCancel() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:CANCEL',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Sabre_CalDAV_Schedule_IMip_Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);
        $this->assertHTTPStatus(200, $req);

        $this->assertEquals(array(
            array(
                'to' => 'user2@example.org',
                'subject' => 'Cancelled event: An invitation',
                'body' => implode("\r\n", $body) . "\r\n",
                'headers' => array(
                    'Reply-To: user1.sabredav@sabredav.org',
                    'From: server@example.org',
                    'Content-Type: text/calendar; method=CANCEL; charset=utf-8',
                    'X-Sabre-Version: ' . Sabre_DAV_Version::VERSION . '-' . Sabre_DAV_Version::STABILITY,
                ),
           )
        ), $handler->getSentEmails());


    }
}
