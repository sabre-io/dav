<?php

class Sabre_DAV_Locks_PluginTest extends Sabre_DAV_AbstractServer {

    function setUp() {

        parent::setUp();
        mkdir('temp/locksdir');
        $locksBackend = new Sabre_DAV_Locks_Backend_FS('temp/locksdir');
        $locksPlugin = new Sabre_DAV_Locks_Plugin($locksBackend);
        $this->server->addPlugin($locksPlugin);

    }

    function testLockNoBody() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'LOCK',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/xml; charset=utf-8',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 400 Bad request',$this->response->status);

    }

    function testLock() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'LOCK',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:"> 
    <D:lockscope><D:exclusive/></D:lockscope> 
    <D:locktype><D:write/></D:locktype> 
    <D:owner> 
        <D:href>http://example.org/~ejw/contact.html</D:href> 
    </D:owner> 
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8',$this->response->headers['Content-Type']);
        $this->assertTrue(preg_match('/^opaquelocktoken:(.*)$/',$this->response->headers['Lock-Token'])===1,'We did not get a valid Locktoken back (' . $this->response->headers['Lock-Token'] . ')');

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status,'Got an incorrect status back. Response body: ' . $this->response->body);

    }

    function testDoubleLock() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'LOCK',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:"> 
    <D:lockscope><D:exclusive/></D:lockscope> 
    <D:locktype><D:write/></D:locktype> 
    <D:owner> 
        <D:href>http://example.org/~ejw/contact.html</D:href> 
    </D:owner> 
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->response = new Sabre_HTTP_ResponseMock();
        $this->server->httpResponse = $this->response;

        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8',$this->response->headers['Content-Type']);

        $this->assertEquals('HTTP/1.1 423 Locked',$this->response->status);

    }

    function testLockNoFile() {

        $serverVars = array(
            'REQUEST_URI'    => '/notfound.txt',
            'REQUEST_METHOD' => 'LOCK',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:"> 
    <D:lockscope><D:exclusive/></D:lockscope> 
    <D:locktype><D:write/></D:locktype> 
    <D:owner> 
        <D:href>http://example.org/~ejw/contact.html</D:href> 
    </D:owner> 
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8',$this->response->headers['Content-Type']);
        $this->assertTrue(preg_match('/^opaquelocktoken:(.*)$/',$this->response->headers['Lock-Token'])===1,'We did not get a valid Locktoken back (' . $this->response->headers['Lock-Token'] . ')');

        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);

    }

    function testUnlockNoToken() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'UNLOCK',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/xml; charset=utf-8',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 400 Bad request',$this->response->status);

    }

    function testUnlockBadToken() {

        $serverVars = array(
            'REQUEST_URI'     => '/test.txt',
            'REQUEST_METHOD'  => 'UNLOCK',
            'HTTP_LOCK_TOKEN' => '<opaquelocktoken:blablabla>',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'Content-Type' => 'application/xml; charset=utf-8',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 409 Conflict',$this->response->status);

    }

    function testLockPutNoToken() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'LOCK',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:"> 
    <D:lockscope><D:exclusive/></D:lockscope> 
    <D:locktype><D:write/></D:locktype> 
    <D:owner> 
        <D:href>http://example.org/~ejw/contact.html</D:href> 
    </D:owner> 
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8',$this->response->headers['Content-Type']);
        $this->assertTrue(preg_match('/^opaquelocktoken:(.*)$/',$this->response->headers['Lock-Token'])===1,'We did not get a valid Locktoken back (' . $this->response->headers['Lock-Token'] . ')');

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8',$this->response->headers['Content-Type']);
        $this->assertTrue(preg_match('/^opaquelocktoken:(.*)$/',$this->response->headers['Lock-Token'])===1,'We did not get a valid Locktoken back (' . $this->response->headers['Lock-Token'] . ')');

        $this->assertEquals('HTTP/1.1 423 Locked',$this->response->status);

    }

    function testLockPutBadToken() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'LOCK',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:"> 
    <D:lockscope><D:exclusive/></D:lockscope> 
    <D:locktype><D:write/></D:locktype> 
    <D:owner> 
        <D:href>http://example.org/~ejw/contact.html</D:href> 
    </D:owner> 
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8',$this->response->headers['Content-Type']);
        $this->assertTrue(preg_match('/^opaquelocktoken:(.*)$/',$this->response->headers['Lock-Token'])===1,'We did not get a valid Locktoken back (' . $this->response->headers['Lock-Token'] . ')');

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF' => '(<opaquelocktoken:token1>)',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8',$this->response->headers['Content-Type']);
        $this->assertTrue(preg_match('/^opaquelocktoken:(.*)$/',$this->response->headers['Lock-Token'])===1,'We did not get a valid Locktoken back (' . $this->response->headers['Lock-Token'] . ')');

        $this->assertEquals('HTTP/1.1 412 Precondition failed',$this->response->status);

    }

    function testLockPutGoodToken() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'LOCK',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:"> 
    <D:lockscope><D:exclusive/></D:lockscope> 
    <D:locktype><D:write/></D:locktype> 
    <D:owner> 
        <D:href>http://example.org/~ejw/contact.html</D:href> 
    </D:owner> 
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8',$this->response->headers['Content-Type']);
        $this->assertTrue(preg_match('/^opaquelocktoken:(.*)$/',$this->response->headers['Lock-Token'])===1,'We did not get a valid Locktoken back (' . $this->response->headers['Lock-Token'] . ')');

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF' => '(<'.$this->response->headers['Lock-Token'].'>)',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8',$this->response->headers['Content-Type']);
        $this->assertTrue(preg_match('/^opaquelocktoken:(.*)$/',$this->response->headers['Lock-Token'])===1,'We did not get a valid Locktoken back (' . $this->response->headers['Lock-Token'] . ')');

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);

    }


}
