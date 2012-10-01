<?php

class Sabre_DAV_HTTPPReferParsingTest extends PHPUnit_Framework_TestCase {

    function testParseSimple() {

        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_PREFER' => 'result-asynch',
        ));

        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
            'result-asynch' => true,
        ), $server->getHTTPPrefer());

    }

    function testParseValue() {

        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_PREFER' => 'wait=10',
        ));

        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
            'wait' => 10,
        ), $server->getHTTPPrefer());

    }

    function testParseMultiple() {

        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_PREFER' => 'result-minimal, strict,lenient',
        ));

        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
            'result-minimal' => true,
            'strict' => true,
            'lenient' => true,
        ), $server->getHTTPPrefer());

    }

    function testParseWeirdValue() {

        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_PREFER' => 'BOOOH',
        ));

        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
        ), $server->getHTTPPrefer());

    }

    function testBrief() {

        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_BRIEF' => 't',
        ));

        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
            'result-minimal' => true,
        ), $server->getHTTPPrefer());

    }

}
