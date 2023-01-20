<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

class ServerSimpleTest extends AbstractServer
{
    public function testConstructArray()
    {
        $nodes = [
            new SimpleCollection('hello'),
        ];

        $server = new Server($nodes);
        $this->assertEquals($nodes[0], $server->tree->getNodeForPath('hello'));
    }

    public function testConstructInvalidArg()
    {
        $this->expectException('Sabre\DAV\Exception');
        $server = new Server(1);
    }

    public function testOptions()
    {
        $request = new HTTP\Request('OPTIONS', '/');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals([
            'DAV' => ['1, 3, extended-mkcol'],
            'MS-Author-Via' => ['DAV'],
            'Allow' => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
            'Accept-Ranges' => ['bytes'],
            'Content-Length' => ['0'],
            'X-Sabre-Version' => [Version::VERSION],
        ], $this->response->getHeaders());

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('', $this->response->getBodyAsString());
    }

    public function testOptionsUnmapped()
    {
        $request = new HTTP\Request('OPTIONS', '/unmapped');
        $this->server->httpRequest = $request;

        $this->server->exec();

        $this->assertEquals([
            'DAV' => ['1, 3, extended-mkcol'],
            'MS-Author-Via' => ['DAV'],
            'Allow' => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT, MKCOL'],
            'Accept-Ranges' => ['bytes'],
            'Content-Length' => ['0'],
            'X-Sabre-Version' => [Version::VERSION],
        ], $this->response->getHeaders());

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('', $this->response->getBodyAsString());
    }

    public function testNonExistantMethod()
    {
        $serverVars = [
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'BLABLA',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(501, $this->response->status);
    }

    public function testBaseUri()
    {
        $serverVars = [
            'REQUEST_URI' => '/blabla/test.txt',
            'REQUEST_METHOD' => 'GET',
        ];
        $filename = $this->tempDir.'/test.txt';

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->setBaseUri('/blabla/');
        $this->assertEquals('/blabla/', $this->server->getBaseUri());
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/octet-stream'],
            'Content-Length' => [13],
            'Last-Modified' => [HTTP\toDate(new \DateTime('@'.filemtime($filename)))],
            'ETag' => ['"'.sha1(fileinode($filename).filesize($filename).filemtime($filename)).'"'],
            ],
            $this->response->getHeaders()
        );

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));
    }

    public function testBaseUriAddSlash()
    {
        $tests = [
            '/' => '/',
            '/foo' => '/foo/',
            '/foo/' => '/foo/',
            '/foo/bar' => '/foo/bar/',
            '/foo/bar/' => '/foo/bar/',
        ];

        foreach ($tests as $test => $result) {
            $this->server->setBaseUri($test);

            $this->assertEquals($result, $this->server->getBaseUri());
        }
    }

    public function testCalculateUri()
    {
        $uris = [
            'http://www.example.org/root/somepath',
            '/root/somepath',
            '/root/somepath/',
            '//root/somepath/',
            '///root///somepath///',
        ];

        $this->server->setBaseUri('/root/');

        foreach ($uris as $uri) {
            $this->assertEquals('somepath', $this->server->calculateUri($uri));
        }

        $this->server->setBaseUri('/root');

        foreach ($uris as $uri) {
            $this->assertEquals('somepath', $this->server->calculateUri($uri));
        }

        $this->assertEquals('', $this->server->calculateUri('/root'));

        $this->server->setBaseUri('/');

        foreach ($uris as $uri) {
            $this->assertEquals('root/somepath', $this->server->calculateUri($uri));
        }

        $this->assertEquals('', $this->server->calculateUri(''));
    }

    public function testCalculateUriSpecialChars()
    {
        $uris = [
            'http://www.example.org/root/%C3%A0fo%C3%B3',
            '/root/%C3%A0fo%C3%B3',
            '/root/%C3%A0fo%C3%B3/',
        ];

        $this->server->setBaseUri('/root/');

        foreach ($uris as $uri) {
            $this->assertEquals("\xc3\xa0fo\xc3\xb3", $this->server->calculateUri($uri));
        }

        $this->server->setBaseUri('/root');

        foreach ($uris as $uri) {
            $this->assertEquals("\xc3\xa0fo\xc3\xb3", $this->server->calculateUri($uri));
        }

        $this->server->setBaseUri('/');

        foreach ($uris as $uri) {
            $this->assertEquals("root/\xc3\xa0fo\xc3\xb3", $this->server->calculateUri($uri));
        }
    }

    public function testCalculateUriBreakout()
    {
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $uri = '/path1/';

        $this->server->setBaseUri('/path2/');
        $this->server->calculateUri($uri);
    }

    public function testGuessBaseUri()
    {
        $serverVars = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/index.php/root',
            'PATH_INFO' => '/root',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());
    }

    /**
     * @depends testGuessBaseUri
     */
    public function testGuessBaseUriPercentEncoding()
    {
        $serverVars = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/index.php/dir/path2/path%20with%20spaces',
            'PATH_INFO' => '/dir/path2/path with spaces',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());
    }

    /**
     * @depends testGuessBaseUri
     */
    /*
    function testGuessBaseUriPercentEncoding2() {

        $this->markTestIncomplete('This behaviour is not yet implemented');
        $serverVars = [
            'REQUEST_URI' => '/some%20directory+mixed/index.php/dir/path2/path%20with%20spaces',
            'PATH_INFO'   => '/dir/path2/path with spaces',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/some%20directory+mixed/index.php/', $server->guessBaseUri());

    }*/

    public function testGuessBaseUri2()
    {
        $serverVars = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/index.php/root/',
            'PATH_INFO' => '/root/',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());
    }

    public function testGuessBaseUriNoPathInfo()
    {
        $serverVars = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/index.php/root',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/', $server->guessBaseUri());
    }

    public function testGuessBaseUriNoPathInfo2()
    {
        $httpRequest = new HTTP\Request('GET', '/a/b/c/test.php');
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/', $server->guessBaseUri());
    }

    /**
     * @depends testGuessBaseUri
     */
    public function testGuessBaseUriQueryString()
    {
        $serverVars = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/index.php/root?query_string=blabla',
            'PATH_INFO' => '/root',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());
    }

    /**
     * @depends testGuessBaseUri
     */
    public function testGuessBaseUriBadConfig()
    {
        $this->expectException('Sabre\DAV\Exception');
        $serverVars = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/index.php/root/heyyy',
            'PATH_INFO' => '/root',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $server->guessBaseUri();
    }

    public function testTriggerException()
    {
        $serverVars = [
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'FOO',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $httpRequest;
        $this->server->on('beforeMethod:*', [$this, 'exceptionTrigger']);
        $this->server->exec();

        $this->assertEquals([
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(500, $this->response->status);
    }

    public function exceptionTrigger($request, $response)
    {
        throw new Exception('Hola');
    }

    public function testReportNotFound()
    {
        $serverVars = [
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'REPORT',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->httpRequest->setBody('<?xml version="1.0"?><bla:myreport xmlns:bla="http://www.rooftopsolutions.nl/NS"></bla:myreport>');
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            ],
            $this->response->getHeaders()
        );

        $this->assertEquals(415, $this->response->status, 'We got an incorrect status back. Full response body follows: '.$this->response->getBodyAsString());
    }

    public function testReportIntercepted()
    {
        $serverVars = [
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'REPORT',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->httpRequest->setBody('<?xml version="1.0"?><bla:myreport xmlns:bla="http://www.rooftopsolutions.nl/NS"></bla:myreport>');
        $this->server->on('report', [$this, 'reportHandler']);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'testheader' => ['testvalue'],
            ],
            $this->response->getHeaders()
        );

        $this->assertEquals(418, $this->response->status, 'We got an incorrect status back. Full response body follows: '.$this->response->getBodyAsString());
    }

    public function reportHandler($reportName, $result, $path)
    {
        if ('{http://www.rooftopsolutions.nl/NS}myreport' == $reportName) {
            $this->server->httpResponse->setStatus(418);
            $this->server->httpResponse->setHeader('testheader', 'testvalue');

            return false;
        } else {
            return;
        }
    }

    public function testGetPropertiesForChildren()
    {
        $result = $this->server->getPropertiesForChildren('', [
            '{DAV:}getcontentlength',
        ]);

        $expected = [
            'test.txt' => ['{DAV:}getcontentlength' => 13],
            'dir/' => [],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * There are certain cases where no HTTP status may be set. We need to
     * intercept these and set it to a default error message.
     */
    public function testNoHTTPStatusSet()
    {
        $this->server->on('method:GET', function () { return false; }, 1);
        $this->server->httpRequest = new HTTP\Request('GET', '/');
        $this->server->exec();
        $this->assertEquals(500, $this->response->getStatus());
    }
}
