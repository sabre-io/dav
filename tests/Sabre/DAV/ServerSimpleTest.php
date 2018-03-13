<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\HTTP;

class ServerSimpleTest extends AbstractServer{

    function testConstructArray() {

        $nodes = [
            new SimpleCollection('hello')
        ];

        $server = new Server($nodes, null, null, function() {});
        $this->assertEquals($nodes[0], $server->tree->getNodeForPath('hello'));

    }


    /**
     * @expectedException \Sabre\DAV\Exception
     */
    function testConstructInvalidArg() {
        $server = new Server(1, null,  null, function() {});
    }

    function testOptions() {

        $request = new ServerRequest('OPTIONS', '/');

        $response = $this->server->handle($request);

        $this->assertEquals([
            'DAV'             => ['1, 3, extended-mkcol'],
            'MS-Author-Via'   => ['DAV'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
            'Accept-Ranges'   => ['bytes'],
            'Content-Length'  => ['0'],

        ], $response->getHeaders());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());

    }

    function testOptionsUnmapped() {

        $request = new ServerRequest('OPTIONS', '/unmapped');


        $response = $this->server->handle($request);

        $this->assertEquals([
            'DAV'             => ['1, 3, extended-mkcol'],
            'MS-Author-Via'   => ['DAV'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT, MKCOL'],
            'Accept-Ranges'   => ['bytes'],
            'Content-Length'  => ['0'],

        ], $response->getHeaders());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());

    }

    function testNonExistantMethod() {

        $response = $this->server->handle(new ServerRequest('BLABLA', '/'));

        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(501, $response->getStatusCode());


    }

    function testBaseUri() {

        $filename = $this->tempDir . '/test.txt';

        $request = new ServerRequest('GET', '/blabla/test.txt');
        $this->server->setBaseUri('/blabla/');
        $this->assertEquals('/blabla/', $this->server->getBaseUri());

        $response = $this->server->handle($request);


        $this->assertEquals([

            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\toDate(new \DateTime('@' . filemtime($filename)))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $response->getHeaders()
         );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Test contents', $response->getBody()->getContents());

    }

    function testBaseUriAddSlash() {

        $tests = [
            '/'         => '/',
            '/foo'      => '/foo/',
            '/foo/'     => '/foo/',
            '/foo/bar'  => '/foo/bar/',
            '/foo/bar/' => '/foo/bar/',
        ];

        foreach ($tests as $test => $result) {
            $this->server->setBaseUri($test);

            $this->assertEquals($result, $this->server->getBaseUri());

        }

    }

    function testCalculateUri() {

        $uris = [
            'http://www.example.org/root/somepath',
            '/root/somepath',
            '/root/somepath/',
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

    function testCalculateUriSpecialChars() {

        $uris = [
            'http://www.example.org/root/%C3%A0fo%C3%B3',
            '/root/%C3%A0fo%C3%B3',
            '/root/%C3%A0fo%C3%B3/'
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

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testCalculateUriBreakout() {

        $uri = '/path1/';

        $this->server->setBaseUri('/path2/');
        $this->server->calculateUri($uri);

    }

    /**
     */
    function testGuessBaseUri() {

        $httpRequest = new ServerRequest('get', '/index.php/root', [], null, '1.1', [
            'PATH_INFO'      => '/root',
            'REQUEST_URI' => '/index.php/root'
        ]);
        $server = new Server(null, null, null, function(){});

        $this->assertEquals('/index.php/', $server->guessBaseUri($httpRequest));

    }

    /**
     * @depends testGuessBaseUri
     */
    function testGuessBaseUriPercentEncoding() {

        $httpRequest = new ServerRequest('get', '/index.php/dir/path2/path%20with%20spaces', [], null, '1.1', [
            'PATH_INFO' => '/dir/path2/path with spaces',
            'REQUEST_URI' => '/index.php/dir/path2/path%20with%20spaces',
        ]);

        $server = new Server(null, null, null, function(){});
        $this->assertEquals('/index.php/', $server->guessBaseUri($httpRequest));

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

        $httpRequest = new ServerRequest($serverVars);
        $server = new Server(null, null, null, function(){});
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/some%20directory+mixed/index.php/', $server->guessBaseUri());

    }*/

    function testGuessBaseUri2() {
        $httpRequest = new ServerRequest('get',  '/index.php/root/', [], null, '1.1', [
            'PATH_INFO' => '/root/',
            'REQUEST_URI' => '/index.php/root/'
        ]);

        $server = new Server(null, null, null, function(){});
        $this->assertEquals('/index.php/', $server->guessBaseUri($httpRequest));

    }

    function testGuessBaseUriNoPathInfo() {

        $httpRequest = new ServerRequest('GET', '/index.php/root');
        $server = new Server(null, null, null, function(){});
        $this->assertEquals('/', $server->guessBaseUri($httpRequest));

    }

    function testGuessBaseUriNoPathInfo2() {

        $httpRequest = new ServerRequest('GET', '/a/b/c/test.php');
        $server = new Server(null, null, null, function(){});
        $this->assertEquals('/', $server->guessBaseUri($httpRequest));

    }


    /**
     * @depends testGuessBaseUri
     */
    function testGuessBaseUriQueryString() {
        $httpRequest = new ServerRequest('GET', '/index.php/root?query_string=blabla', [], null, '.1.1', [
            'REQUEST_URI' => '/index.php/root?query_string=blabla',
            'PATH_INFO'      => '/root',
        ]);
        $server = new Server(null, null, null, function(){});

        $this->assertEquals('/index.php/', $server->guessBaseUri($httpRequest));

    }

    /**
     * @depends testGuessBaseUri
     * @expectedException \Sabre\DAV\Exception
     */
    function testGuessBaseUriBadConfig() {
        $httpRequest = new ServerRequest('GET',  '/index.php/root/heyyy', [], null, '1.1',[
            'PATH_INFO'      => '/root',
            'REQUEST_URI' => '/index.php/root/heyyy',
        ]);
        $server = new Server(null, null, null, function(){});
        $server->guessBaseUri($httpRequest);

    }

    function testTriggerException() {

        $request = new ServerRequest('FOO', '/');
        $this->server->on('beforeMethod:*', function() {
            throw new Exception('Hola');
        });

        $response = $this->server->handle($request);
        $this->assertEquals([
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $response->getHeaders());

        $this->assertEquals(500, $response->getStatusCode());

    }

    function testReportNotFound() {

        $request = new ServerRequest('REPORT', '/', [], '<?xml version="1.0"?><bla:myreport xmlns:bla="http://www.rooftopsolutions.nl/NS"></bla:myreport>');

        $response = $this->server->handle($request);
        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $response->getHeaders()
         );

        $this->assertEquals(415, $response->getStatusCode(), 'We got an incorrect status back. Full response body follows: ' . $response->getBody()->getContents());

    }

    function testReportIntercepted() {

        $request = new ServerRequest('REPORT', '/', [], '<?xml version="1.0"?><bla:myreport xmlns:bla="http://www.rooftopsolutions.nl/NS"></bla:myreport>');
        $this->server->on('report', function($reportName) {
            if ($reportName === '{http://www.rooftopsolutions.nl/NS}myreport') {
                $this->server->httpResponse->setStatus(418);
                $this->server->httpResponse->setHeader('testheader', 'testvalue');
                return false;
            }
        });

        $response = $this->server->handle($request);

        $this->assertEquals([

            'testheader'      => ['testvalue'],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(418, $response->getStatusCode(), 'We got an incorrect status back. Full response body follows: ' . $response->getBody()->getContents());
    }


    function testGetPropertiesForChildren() {

        $result = $this->server->getPropertiesForChildren('', [
            '{DAV:}getcontentlength',
        ]);

        $expected = [
            'test.txt' => ['{DAV:}getcontentlength' => 13],
            'dir/'     => [],
        ];

        $this->assertEquals($expected, $result);

    }

    /**
     * There are certain cases where no HTTP status may be set. We need to
     * intercept these and set it to a default error message.
     */
    function testNoHTTPStatusSet() {
        $called = false;
        $this->server->on('method:GET', function() use (&$called) {
            $called = true;
            return false;
        }, 1);
        $request = new ServerRequest('GET', '/');

        $response = $this->server->handle($request);
        $this->assertTrue($called);
        $this->assertEquals(500, $response->getStatusCode(), print_r($response->getHeaders(), true));

    }

}
