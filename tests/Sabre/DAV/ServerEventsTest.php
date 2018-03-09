<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class ServerEventsTest extends AbstractServer {

    private $tempPath;

    private $exception;

    function testAfterBind() {

        $this->server->on('afterBind', [$this, 'afterBindHandler']);
        $newPath = 'afterBind';

        $this->tempPath = '';
        $this->server->createFile($newPath, 'body');
        $this->assertEquals($newPath, $this->tempPath);

    }

    function afterBindHandler($path) {

       $this->tempPath = $path;

    }

    function testAfterResponse() {

        $mock = $this->getMockBuilder('stdClass')
            ->setMethods(['afterResponseCallback'])
            ->getMock();
        $mock->expects($this->once())->method('afterResponseCallback');

        $this->server->on('afterResponse', [$mock, 'afterResponseCallback']);

        $this->server->handle(new ServerRequest('GET','/test.txt'));



    }

    function testBeforeBindCancel() {

        $this->server->on('beforeBind', [$this, 'beforeBindCancelHandler']);
        $this->assertFalse($this->server->createFile('bla', 'body'));

        // Also testing put()
        $response = $this->server->handle(new ServerRequest('PUT','/barbar'));
        $responseBody = $response->getBody()->getContents();


        $this->assertEquals(500, $response->getStatusCode(), $responseBody);

    }

    function beforeBindCancelHandler($path) {

        return false;

    }

    function testException() {

        $exception = null;
        $this->server->on('exception', function(Exception $e) use (&$exception) {
            $exception = $e;
        });

        $this->server->handle(new ServerRequest('GET', '/not/existing'));
        $this->assertInstanceOf(NotFound::class, $exception);
    }

    function exceptionHandler(Exception $exception) {

        $this->exception = $exception;

    }

    function testMethod() {

        $k = 1;
        $this->server->on('method:*', function($request, $response) use (&$k) {

            $k += 1;

            return false;

        });
        $this->server->on('method:*', function($request, $response) use (&$k) {

            $k += 2;

            return false;

        });

        try {
            $this->server->handle(new ServerRequest('BLABLA', '/'));
        } catch (Exception $e) {}

        // Fun fact, PHP 7.1 changes the order when sorting-by-callback.
        $this->assertTrue($k >= 2 && $k <= 3);

    }

}
