<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class ServerPreconditionsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfMatchNoNode() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});

        $httpRequest = new ServerRequest('GET', '/bar', ['If-Match' => '*']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse);

    }

    /**
     */
    function testIfMatchHasNode() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', ['If-Match' => '*']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }

    /**
     * @expectedException \Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfMatchWrongEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', ['If-Match' => '1234']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse);

    }

    /**
     */
    function testIfMatchCorrectEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', ['If-Match' => '"abc123"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }

    /**
     * Evolution sometimes uses \" instead of " for If-Match headers.
     *
     * @depends testIfMatchCorrectEtag
     */
    function testIfMatchEvolutionEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', ['If-Match' => '\\"abc123\\"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }

    /**
     */
    function testIfMatchMultiple() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', ['If-Match' => '"hellothere", "abc123"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }

    /**
     */
    function testIfNoneMatchNoNode() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/bar', ['If-None-Match' => '*']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }

    /**
     * @expectedException \Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfNoneMatchHasNode() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('POST', '/foo', ['If-None-Match' => '*']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse);

    }

    /**
     */
    function testIfNoneMatchWrongEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('POST', '/foo', ['If-None-Match' => '"1234"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }

    /**
     */
    function testIfNoneMatchWrongEtagMultiple() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('POST', '/foo', ['If-None-Match' => '"1234", "5678"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }

    /**
     * @expectedException \Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfNoneMatchCorrectEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('POST', '/foo', ['If-None-Match' => '"abc123"']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfNoneMatchCorrectEtagMultiple() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('POST', '/foo', ['If-None-Match' => '"1234, "abc123"']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse);

    }

    /**
     */
    function testIfNoneMatchCorrectEtagAsGet() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', ['If-None-Match' => '"abc123"']);
        $this->assertFalse($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $server->httpResponse));


        $this->assertEquals(304, $server->httpResponse->getResponse()->getStatusCode());
        $this->assertEquals(['ETag' => ['"abc123"']], $server->httpResponse->getResponse()->getHeaders());

    }

    /**
     * This was a test written for issue #515.
     */
    function testNoneMatchCorrectEtagEnsureSapiSent() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', ['If-None-Match' => '"abc123"']);
        $this->assertFalse($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $server->httpResponse));

        $response = $server->handle($httpRequest);

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals([
            'ETag'            => ['"abc123"'],

        ], $response->getHeaders());
    }

    /**
     */
    function testIfModifiedSinceUnModified() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', [
            'If-Modified-Since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
        ]);
        $this->assertFalse($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $server->httpResponse));

        $response = $server->httpResponse->getResponse();
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals([
            'Last-Modified' => ['Sat, 06 Apr 1985 23:30:00 GMT'],
        ], $response->getHeaders());

    }


    /**
     */
    function testIfModifiedSinceModified() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', [
            'If-Modified-Since' => 'Tue, 06 Nov 1984 08:49:37 GMT',
        ]);

        $httpResponse = new HTTP\ResponseMock();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }

    /**
     */
    function testIfModifiedSinceInvalidDate() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', [
            'If-Modified-Since' => 'Your mother',
        ]);
        $httpResponse = new HTTP\ResponseMock();

        // Invalid dates must be ignored, so this should return true
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }

    /**
     */
    function testIfModifiedSinceInvalidDate2() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', [
            'If-Unmodified-Since' => 'Sun, 06 Nov 1994 08:49:37 EST',
        ]);
        $httpResponse = new HTTP\ResponseMock();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }


    /**
     */
    function testIfUnmodifiedSinceUnModified() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', [
            'If-Unmodified-Since' => 'Sun, 06 Nov 1994 08:49:37 GMT',
        ]);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

    }


    /**
     * @expectedException \Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfUnmodifiedSinceModified() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', [
            'If-Unmodified-Since' => 'Tue, 06 Nov 1984 08:49:37 GMT',
        ]);
        $httpResponse = new HTTP\ResponseMock();
        $server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse);

    }

    /**
     */
    function testIfUnmodifiedSinceInvalidDate() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root, null, null, function(){});
        $httpRequest = new ServerRequest('GET', '/foo', [
            'If-Unmodified-Since' => 'Sun, 06 Nov 1984 08:49:37 CET',
        ]);
        $httpResponse = new HTTP\ResponseMock();
        $this->assertTrue($server->checkPreconditions(new Psr7RequestWrapper($httpRequest), $httpResponse));

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
