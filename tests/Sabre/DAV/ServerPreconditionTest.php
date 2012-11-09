<?php

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class ServerPreconditionsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Sabre\DAV\Server::checkPreconditions
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfMatchNoNode() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MATCH' => '*',
            'REQUEST_URI'   => '/bar'
        ));
        $server->httpRequest = $httpRequest;

        $server->checkPreconditions();

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    function testIfMatchHasNode() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MATCH' => '*',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfMatchWrongEtag() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MATCH' => '1234',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $server->checkPreconditions();

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    function testIfMatchCorrectEtag() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MATCH' => '"abc123"',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * Evolution sometimes uses \" instead of " for If-Match headers.
     *
     * @covers \Sabre\DAV\Server::checkPreconditions
     * @depends testIfMatchCorrectEtag
     */
    function testIfMatchEvolutionEtag() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MATCH' => '\\"abc123\\"',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    function testIfMatchMultiple() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MATCH' => '"hellothere", "abc123"',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    function testIfNoneMatchNoNode() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_NONE_MATCH' => '*',
            'REQUEST_URI'   => '/bar'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfNoneMatchHasNode() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_NONE_MATCH' => '*',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $server->checkPreconditions();

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    function testIfNoneMatchWrongEtag() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_NONE_MATCH' => '"1234"',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    function testIfNoneMatchWrongEtagMultiple() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_NONE_MATCH' => '"1234", "5678"',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    public function testIfNoneMatchCorrectEtag() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_NONE_MATCH' => '"abc123"',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $server->checkPreconditions();

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    public function testIfNoneMatchCorrectEtagMultiple() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_NONE_MATCH' => '"1234", "abc123"',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $server->checkPreconditions();

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    public function testIfNoneMatchCorrectEtagAsGet() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_NONE_MATCH' => '"abc123"',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new HTTP\ResponseMock();

        $this->assertFalse($server->checkPreconditions(true));
        $this->assertEquals('HTTP/1.1 304 Not Modified',$server->httpResponse->status);

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    public function testIfModifiedSinceUnModified() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new HTTP\ResponseMock();
        $this->assertFalse($server->checkPreconditions());

        $this->assertEquals('HTTP/1.1 304 Not Modified',$server->httpResponse->status);
        $this->assertEquals(array(
            'Last-Modified' => 'Sat, 06 Apr 1985 23:30:00 GMT',
        ), $server->httpResponse->headers);

    }


    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    public function testIfModifiedSinceModified() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MODIFIED_SINCE' => 'Tue, 06 Nov 1984 08:49:37 GMT',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new HTTP\ResponseMock();
        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    public function testIfModifiedSinceInvalidDate() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MODIFIED_SINCE' => 'Your mother',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new HTTP\ResponseMock();

        // Invalid dates must be ignored, so this should return true
        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    public function testIfModifiedSinceInvalidDate2() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 06 Nov 1994 08:49:37 EST',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new HTTP\ResponseMock();
        $this->assertTrue($server->checkPreconditions());

    }


    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    public function testIfUnmodifiedSinceUnModified() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_UNMODIFIED_SINCE' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $this->assertTrue($server->checkPreconditions());

    }


    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    public function testIfUnmodifiedSinceModified() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_UNMODIFIED_SINCE' => 'Tue, 06 Nov 1984 08:49:37 GMT',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new HTTP\ResponseMock();
        $server->checkPreconditions();

    }

    /**
     * @covers \Sabre\DAV\Server::checkPreconditions
     */
    public function testIfUnmodifiedSinceInvalidDate() {

        $root = new SimpleCollection('root',array(new ServerPreconditionsNode()));
        $server = new Server($root);
        $httpRequest = new HTTP\Request(array(
            'HTTP_IF_UNMODIFIED_SINCE' => 'Sun, 06 Nov 1984 08:49:37 CET',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new HTTP\ResponseMock();
        $this->assertTrue($server->checkPreconditions());

    }


}

class ServerPreconditionsNode extends File {

    function getETag() {

        return '"abc123"';

    }

    function getLastModified() {

        /* my birthday & time, I believe */
        return strtotime('1985-04-07 01:30 +02:00');

    }

    function getName() {

        return 'foo';

    }

}
