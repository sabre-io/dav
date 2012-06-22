<?php

namespace Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class ServerEventsTest extends AbstractServer {

    private $tempPath;

    private $exception;

    function testAfterBind() {

        $this->server->subscribeEvent('afterBind',array($this,'afterBindHandler'));
        $newPath = 'afterBind';

        $this->tempPath = '';
        $this->server->createFile($newPath,'body');
        $this->assertEquals($newPath, $this->tempPath);

    }

    function afterBindHandler($path) {

       $this->tempPath = $path;

    }

    function testBeforeBindCancel() {

        $this->server->subscribeEvent('beforeBind', array($this,'beforeBindCancelHandler'));
        $this->assertFalse($this->server->createFile('bla','body'));

        // Also testing put()
        $req = new HTTP\Request(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/barbar',
        ));

        $this->server->httpRequest = $req;
        $this->server->exec();

        $this->assertEquals('',$this->server->httpResponse->status);

    }

    function beforeBindCancelHandler() {

        return false;

    }

    function testException() {

        $this->server->subscribeEvent('exception', array($this, 'exceptionHandler'));

        $req = new HTTP\Request(array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/not/exisitng',
        ));
        $this->server->httpRequest = $req;
        $this->server->exec();

        $this->assertInstanceOf('Sabre\\DAV\\Exception\\NotFound', $this->exception);

    }

    function exceptionHandler(Exception $exception) {

        $this->exception = $exception;

    }

}
