<?php

require_once 'Sabre/DAVServerTest.php';

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

    function testPostPassThruNoOriginator() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/admin/outbox',
        ));

        $this->assertHTTPStatus(400, $req);

    }

    function testPostPassThruNoRecipient() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'mailto:orig@example.org',
        ));

        $this->assertHTTPStatus(400, $req);

    }

    function testPostPassThruBadOriginator() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'nomailto:orig@example.org',
            'HTTP_RECIPIENT'  => 'mailto:user1@example.org',
        ));

        $this->assertHTTPStatus(400, $req);

    }

    function testPostPassThruBadRecipient() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'mailto:orig@example.org',
            'HTTP_RECIPIENT'  => 'http://user1@example.org, mailto:user2@example.org',
        ));

        $this->assertHTTPStatus(400, $req);

    }

    function testPostPassIncorrectOriginator() {

        $req = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'mailto:orig@example.org',
            'HTTP_RECIPIENT'  => 'mailto:user1@example.org, mailto:user2@example.org',
        ));

        $this->assertHTTPStatus(403, $req);

    }
} 

?>
