<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;

class PSR3Test extends \PHPUnit_Framework_TestCase {

    function testIsLoggerAware() {

        $server = new Server(null, null, null, function(){});
        $this->assertInstanceOf(
            'Psr\Log\LoggerAwareInterface',
            $server
        );

    }

    function testGetNullLoggerByDefault() {

        $server = new Server(null, null, null, function(){});
        $this->assertInstanceOf(
            'Psr\Log\NullLogger',
            $server->getLogger()
        );

    }

    function testSetLogger() {

        $server = new Server(null, null, null, function(){});
        $logger = new MockLogger();

        $server->setLogger($logger);

        $this->assertEquals(
            $logger,
            $server->getLogger()
        );

    }

    /**
     * Start the server, trigger an exception and see if the logger captured
     * it.
     */
    function testLogException() {

        $server = new Server(null, null, null, function(){});
        $logger = new MockLogger();

        $server->setLogger($logger);

        // Creating a fake environment to execute http requests in.
        $request = new ServerRequest(
            'GET',
            '/not-found',
            []
        );

        $response = $server->handle($request);

        // The request should have triggered a 404 status.
        $this->assertEquals(404, $response->getStatusCode());

        // We should also see this in the PSR-3 log.
        $this->assertEquals(1, count($logger->logs));

        $logItem = $logger->logs[0];

        $this->assertEquals(
            \Psr\Log\LogLevel::INFO,
            $logItem[0]
        );

        $this->assertInstanceOf(
            'Exception',
            $logItem[2]['exception']
        );

    }

}
