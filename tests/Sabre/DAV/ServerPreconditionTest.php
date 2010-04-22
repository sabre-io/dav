<?php

require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_DAV_ServerPreconditionsTest extends PHPUnit_Framework_TestCase {

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     * @expectedException Sabre_DAV_Exception_PreconditionFailed
     */
    function testIfMatchNoNode() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_MATCH' => '*',
            'REQUEST_URI'   => '/bar'
        ));
        $server->httpRequest = $httpRequest;

        $server->checkPreconditions();

    } 

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     */
    function testIfMatchHasNode() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_MATCH' => '*',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     * @expectedException Sabre_DAV_Exception_PreconditionFailed
     */
    function testIfMatchWrongEtag() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_MATCH' => '1234',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $server->checkPreconditions();

    } 

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     */
    function testIfMatchCorrectEtag() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_MATCH' => 'abc123',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    } 

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     */
    function testIfNoneMatchNoNode() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_NONE_MATCH' => '*',
            'REQUEST_URI'   => '/bar'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    } 

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     * @expectedException Sabre_DAV_Exception_PreconditionFailed
     */
    function testIfNoneMatchHasNode() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_NONE_MATCH' => '*',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $server->checkPreconditions();

    }

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     */
    function testIfNoneMatchWrongEtag() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_NONE_MATCH' => '1234',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $this->assertTrue($server->checkPreconditions());

    } 

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     * @expectedException Sabre_DAV_Exception_PreconditionFailed
     */
    public function testIfNoneMatchCorrectEtag() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_NONE_MATCH' => 'abc123',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;

        $server->checkPreconditions();

    }

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     */
    public function testIfModifiedSinceUnModified() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new Sabre_HTTP_ResponseMock();
        $this->assertFalse($server->checkPreconditions());

        $this->assertEquals('HTTP/1.1 304 Not Modified',$server->httpResponse->status);

    }

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     */
    public function testIfModifiedSinceModified() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 06 Nov 1984 08:49:37 GMT',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new Sabre_HTTP_ResponseMock();
        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     */
    public function testIfUnmodifiedSinceUnModified() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_UNMODIFIED_SINCE' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $this->assertTrue($server->checkPreconditions());

    }

    /**
     * @covers Sabre_DAV_Server::checkPreconditions
     * @expectedException Sabre_DAV_Exception_PreconditionFailed
     */
    public function testIfUnmodifiedSinceModified() {

        $root = new Sabre_DAV_SimpleDirectory('root',array(new Sabre_DAV_ServerPreconditionsNode())); 
        $server = new Sabre_DAV_Server($root);
        $httpRequest = new Sabre_HTTP_Request(array(
            'HTTP_IF_UNMODIFIED_SINCE' => 'Sun, 06 Nov 1984 08:49:37 GMT',
            'REQUEST_URI'   => '/foo'
        ));
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new Sabre_HTTP_ResponseMock();
        $server->checkPreconditions();

    }


}

class Sabre_DAV_ServerPreconditionsNode extends Sabre_DAV_File {

    function getETag() {
    
        return 'abc123';

    }

    function getLastModified() {

        /* my birthdy & time, I believe */
        return strtotime('1985-04-07 01:30 +02:00');

    }

    function getName() {

        return 'foo';

    }

}
