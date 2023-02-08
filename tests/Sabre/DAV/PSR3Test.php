<?php

declare(strict_types=1);

namespace Sabre\DAV;

class PSR3Test extends \PHPUnit\Framework\TestCase
{
    public function testIsLoggerAware()
    {
        $server = new Server();
        self::assertInstanceOf(
            'Psr\Log\LoggerAwareInterface',
            $server
        );
    }

    public function testGetNullLoggerByDefault()
    {
        $server = new Server();
        self::assertInstanceOf(
            'Psr\Log\NullLogger',
            $server->getLogger()
        );
    }

    public function testSetLogger()
    {
        $server = new Server();
        $logger = new MockLogger();

        $server->setLogger($logger);

        self::assertEquals(
            $logger,
            $server->getLogger()
        );
    }

    /**
     * Start the server, trigger an exception and see if the logger captured
     * it.
     */
    public function testLogException()
    {
        $server = new Server();
        $logger = new MockLogger();

        $server->setLogger($logger);

        // Creating a fake environment to execute http requests in.
        $request = new \Sabre\HTTP\Request(
            'GET',
            '/not-found',
            []
        );
        $response = new \Sabre\HTTP\Response();

        $server->httpRequest = $request;
        $server->httpResponse = $response;
        $server->sapi = new \Sabre\HTTP\SapiMock();

        // Executing the request.
        $server->exec();

        // The request should have triggered a 404 status.
        self::assertEquals(404, $response->getStatus());

        // We should also see this in the PSR-3 log.
        self::assertEquals(1, count($logger->logs));

        $logItem = $logger->logs[0];

        self::assertEquals(
            \Psr\Log\LogLevel::INFO,
            $logItem[0]
        );

        self::assertInstanceOf(
            'Exception',
            $logItem[2]['exception']
        );
    }
}
