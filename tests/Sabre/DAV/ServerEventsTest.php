<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

class ServerEventsTest extends AbstractServer
{
    private $tempPath;

    private $exception;

    private $fileProperties;

    public function testAfterBind()
    {
        $this->server->on('afterBind', [$this, 'afterBindHandler']);
        $newPath = 'afterBind';

        $this->tempPath = '';
        $this->server->createFile($newPath, 'body');
        $this->assertEquals($newPath, $this->tempPath);
    }

    public function afterBindHandler($path)
    {
        $this->tempPath = $path;
    }

    public function testAfterResponse()
    {
        $mock = $this->getMockBuilder('stdClass')
            ->setMethods(['afterResponseCallback'])
            ->getMock();
        $mock->expects($this->once())->method('afterResponseCallback');

        $this->server->on('afterResponse', [$mock, 'afterResponseCallback']);

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test.txt',
        ]);

        $this->server->exec();
    }

    public function testBeforeBindCancel()
    {
        $this->server->on('beforeBind', [$this, 'beforeBindCancelHandler']);
        $this->assertFalse($this->server->createFile('bla', 'body'));

        // Also testing put()
        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/barbar',
        ]);

        $this->server->httpRequest = $req;
        $this->server->exec();

        $this->assertEquals(500, $this->server->httpResponse->getStatus());
    }

    public function beforeBindCancelHandler($path)
    {
        return false;
    }

    public function testException()
    {
        $this->server->on('exception', [$this, 'exceptionHandler']);

        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/not/exisitng',
        ]);
        $this->server->httpRequest = $req;
        $this->server->exec();

        $this->assertInstanceOf('Sabre\\DAV\\Exception\\NotFound', $this->exception);
    }

    public function exceptionHandler(Exception $exception)
    {
        $this->exception = $exception;
    }

    public function testMethod()
    {
        $k = 1;
        $this->server->on('method:*', function ($request, $response) use (&$k) {
            ++$k;

            return false;
        });
        $this->server->on('method:*', function ($request, $response) use (&$k) {
            $k += 2;

            return false;
        });

        try {
            $this->server->invokeMethod(
                new HTTP\Request('BLABLA', '/'),
                new HTTP\Response(),
                false
            );
        } catch (Exception $e) {
        }

        // Fun fact, PHP 7.1 changes the order when sorting-by-callback.
        $this->assertTrue($k >= 2 && $k <= 3);
    }

    public function multiStatusHandler(&$fileProperties)
    {
        $this->fileProperties = $fileProperties;
        $fileProperties = array_slice($fileProperties, 0, 1);
    }

    public function testBeforeMultiStatus()
    {
        $this->server->on('beforeMultiStatus', [$this, 'multiStatusHandler']);

        $response = $this->server->generateMultiStatus([
            [
                'href' => 'foo',
                '200' => [
                    'd:getcontentlength' => 10,
                ],
            ],
            [
                'href' => 'bar',
                '200' => [
                    'd:getcontentlength' => 11,
                ],
            ],
        ]);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", 'xmlns\\1="urn:DAV"', $response);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength');
        $this->assertCount(1, $data);

        $this->assertCount(2, $this->fileProperties);
    }
}
