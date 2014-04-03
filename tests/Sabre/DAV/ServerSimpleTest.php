<?php

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';
require_once 'Sabre/DAV/Exception.php';

class Sabre_DAV_ServerSimpleTest extends Sabre_DAV_AbstractServer{

    function testConstructArray() {

        $nodes = array(
            new Sabre_DAV_SimpleCollection('hello')
        );

        $server = new Sabre_DAV_Server($nodes);
        $this->assertEquals($nodes[0], $server->tree->getNodeForPath('hello'));

    }

    /**
     * @expectedException Sabre_DAV_Exception
     */
    function testConstructIncorrectObj() {

        $nodes = array(
            new Sabre_DAV_SimpleCollection('hello'),
            new STDClass(),
        );

        $server = new Sabre_DAV_Server($nodes);

    }

    /**
     * @expectedException Sabre_DAV_Exception
     */
    function testConstructInvalidArg() {

        $server = new Sabre_DAV_Server(1);

    }

    function testGet() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            'Last-Modified' => Sabre_HTTP_Util::toHTTPDate(new DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 200 OK',$this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }
    function testGetHttp10() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'GET',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            'Last-Modified' => Sabre_HTTP_Util::toHTTPDate(new DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.0 200 OK',$this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }

    function testGetDoesntExist() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt_randomblbla',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();
        $this->assertEquals('HTTP/1.1 404 Not Found',$this->response->status);

    }

    function testGetDoesntExist2() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt/randomblbla',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();
        $this->assertEquals('HTTP/1.1 404 Not Found',$this->response->status);

    }

    /**
     * This test should have the exact same result as testGet.
     *
     * The idea is that double slashes // are converted to single ones /
     *
     */
    function testGetDoubleSlash() {

        $serverVars = array(
            'REQUEST_URI'    => '//test.txt',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            'Last-Modified' => Sabre_HTTP_Util::toHTTPDate(new DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 200 OK',$this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }


    function testHEAD() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'HEAD',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            'Last-Modified' => Sabre_HTTP_Util::toHTTPDate(new DateTime('@' .  filemtime($this->tempDir . '/test.txt'))),
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 200 OK',$this->response->status);
        $this->assertEquals('', $this->response->body);

    }

    function testOptions() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'OPTIONS',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'DAV'            => '1, 3, extended-mkcol',
            'MS-Author-Via'  => 'DAV',
            'Allow'          => 'OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT',
            'Accept-Ranges'  => 'bytes',
            'Content-Length' => '0',
            'X-Sabre-Version' => Sabre_DAV_Version::VERSION,
        ),$this->response->headers);

        $this->assertEquals('HTTP/1.1 200 OK',$this->response->status);
        $this->assertEquals('', $this->response->body);


    }
    function testNonExistantMethod() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'BLABLA',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/xml; charset=utf-8',
        ),$this->response->headers);

        $this->assertEquals('HTTP/1.1 501 Not Implemented',$this->response->status);


    }

    function testGETOnCollection() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/xml; charset=utf-8',
        ),$this->response->headers);

        $this->assertEquals('HTTP/1.1 501 Not Implemented',$this->response->status);

    }

    function testHEADOnCollection() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'HEAD',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 200 OK',$this->response->status);

    }

    function testBaseUri() {

        $serverVars = array(
            'REQUEST_URI'    => '/blabla/test.txt',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setBaseUri('/blabla/');
        $this->assertEquals('/blabla/',$this->server->getBaseUri());
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => 13,
            'Last-Modified' => Sabre_HTTP_Util::toHTTPDate(new DateTime('@' . filemtime($this->tempDir . '/test.txt'))),
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 200 OK',$this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }

    function testBaseUriAddSlash() {

        $tests = array(
            '/'         => '/',
            '/foo'      => '/foo/',
            '/foo/'     => '/foo/',
            '/foo/bar'  => '/foo/bar/',
            '/foo/bar/' => '/foo/bar/',
        );

        foreach($tests as $test=>$result) {
            $this->server->setBaseUri($test);

            $this->assertEquals($result, $this->server->getBaseUri());

        }

    }

    function testCalculateUri() {

        $uris = array(
            'http://www.example.org/root/somepath',
            '/root/somepath',
            '/root/somepath/',
        );

        $this->server->setBaseUri('/root/');

        foreach($uris as $uri) {

            $this->assertEquals('somepath',$this->server->calculateUri($uri));

        }

        $this->server->setBaseUri('/root');

        foreach($uris as $uri) {

            $this->assertEquals('somepath',$this->server->calculateUri($uri));

        }

        $this->assertEquals('', $this->server->calculateUri('/root'));

    }

    function testCalculateUriSpecialChars() {

        $uris = array(
            'http://www.example.org/root/%C3%A0fo%C3%B3',
            '/root/%C3%A0fo%C3%B3',
            '/root/%C3%A0fo%C3%B3/'
        );

        $this->server->setBaseUri('/root/');

        foreach($uris as $uri) {

            $this->assertEquals("\xc3\xa0fo\xc3\xb3",$this->server->calculateUri($uri));

        }

        $this->server->setBaseUri('/root');

        foreach($uris as $uri) {

            $this->assertEquals("\xc3\xa0fo\xc3\xb3",$this->server->calculateUri($uri));

        }

        $this->server->setBaseUri('/');

        foreach($uris as $uri) {

            $this->assertEquals("root/\xc3\xa0fo\xc3\xb3",$this->server->calculateUri($uri));

        }

    }

    function testBaseUriCheck() {

        $uris = array(
            'http://www.example.org/root/somepath',
            '/root/somepath',
            '/root/somepath/'
        );

        try {

            $this->server->setBaseUri('root/');
            $this->server->calculateUri('/root/testuri');

            $this->fail('Expected an exception');

        } catch (Sabre_DAV_Exception_Forbidden $e) {

            // This was expected

        }

    }

    /**
     * @covers Sabre_DAV_Server::guessBaseUri
     */
    function testGuessBaseUri() {

        $serverVars = array(
            'REQUEST_URI' => '/index.php/root',
            'PATH_INFO'   => '/root',
        );

        $httpRequest = new Sabre_HTTP_Request($serverVars);
        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());

    }

    /**
     * @depends testGuessBaseUri
     * @covers Sabre_DAV_Server::guessBaseUri
     */
    function testGuessBaseUriPercentEncoding() {

        $serverVars = array(
            'REQUEST_URI' => '/index.php/dir/path2/path%20with%20spaces',
            'PATH_INFO'   => '/dir/path2/path with spaces',
        );

        $httpRequest = new Sabre_HTTP_Request($serverVars);
        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());

    }

    /**
     * @depends testGuessBaseUri
     * @covers Sabre_DAV_Server::guessBaseUri
     */
    /*
    function testGuessBaseUriPercentEncoding2() {

        $this->markTestIncomplete('This behaviour is not yet implemented');
        $serverVars = array(
            'REQUEST_URI' => '/some%20directory+mixed/index.php/dir/path2/path%20with%20spaces',
            'PATH_INFO'   => '/dir/path2/path with spaces',
        );

        $httpRequest = new Sabre_HTTP_Request($serverVars);
        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/some%20directory+mixed/index.php/', $server->guessBaseUri());

    }*/

    function testGuessBaseUri2() {

        $serverVars = array(
            'REQUEST_URI' => '/index.php/root/',
            'PATH_INFO'   => '/root/',
        );

        $httpRequest = new Sabre_HTTP_Request($serverVars);
        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());

    }

    function testGuessBaseUriNoPathInfo() {

        $serverVars = array(
            'REQUEST_URI' => '/index.php/root',
        );

        $httpRequest = new Sabre_HTTP_Request($serverVars);
        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/', $server->guessBaseUri());

    }

    function testGuessBaseUriNoPathInfo2() {

        $serverVars = array(
            'REQUEST_URI' => '/a/b/c/test.php',
        );

        $httpRequest = new Sabre_HTTP_Request($serverVars);
        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/', $server->guessBaseUri());

    }


    /**
     * @covers Sabre_DAV_Server::guessBaseUri
     * @depends testGuessBaseUri
     */
    function testGuessBaseUriQueryString() {

        $serverVars = array(
            'REQUEST_URI' => '/index.php/root?query_string=blabla',
            'PATH_INFO'   => '/root',
        );

        $httpRequest = new Sabre_HTTP_Request($serverVars);
        $server = new Sabre_DAV_Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());

    }

    function testTriggerException() {

        $this->server->subscribeEvent('beforeMethod',array($this,'exceptionTrigger'));
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/xml; charset=utf-8',
        ),$this->response->headers);

        $this->assertEquals('HTTP/1.1 500 Internal Server Error',$this->response->status);

    }

    function exceptionTrigger() {

        throw new Sabre_DAV_Exception('Hola');

    }

    function testReportNotFound() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'REPORT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->httpRequest->setBody('<?xml version="1.0"?><bla:myreport xmlns:bla="http://www.rooftopsolutions.nl/NS"></bla:myreport>');
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/xml; charset=utf-8',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 403 Forbidden',$this->response->status,'We got an incorrect status back. Full response body follows: ' . $this->response->body);

    }

    function testReportIntercepted() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'REPORT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->httpRequest->setBody('<?xml version="1.0"?><bla:myreport xmlns:bla="http://www.rooftopsolutions.nl/NS"></bla:myreport>');
        $this->server->subscribeEvent('report',array($this,'reportHandler'));
        $this->server->exec();

        $this->assertEquals(array(
            'testheader' => 'testvalue',
            ),
            $this->response->headers
        );

        $this->assertEquals('HTTP/1.1 418 I\'m a teapot',$this->response->status,'We got an incorrect status back. Full response body follows: ' . $this->response->body);

    }

    function reportHandler($reportName) {

        if ($reportName=='{http://www.rooftopsolutions.nl/NS}myreport') {
            $this->server->httpResponse->sendStatus(418);
            $this->server->httpResponse->setHeader('testheader','testvalue');
            return false;
        }
        else return;

    }

    function testGetPropertiesForChildren() {

        $result = $this->server->getPropertiesForChildren('',array(
            '{DAV:}getcontentlength',
        ));

        $expected = array(
            'test.txt' => array('{DAV:}getcontentlength' => 13),
            'dir/' => array(),
        );

        $this->assertEquals($expected,$result);

    }

}
