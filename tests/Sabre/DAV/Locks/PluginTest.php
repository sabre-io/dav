<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks;

use Sabre\DAV;
use Sabre\HTTP;

class PluginTest extends DAV\AbstractServer
{
    /**
     * @var Plugin
     */
    protected $locksPlugin;

    public function setup(): void
    {
        parent::setUp();
        $locksBackend = new Backend\File(SABRE_TEMPDIR.'/locksdb');
        $locksPlugin = new Plugin($locksBackend);
        $this->server->addPlugin($locksPlugin);
        $this->locksPlugin = $locksPlugin;
    }

    public function testGetInfo()
    {
        self::assertArrayHasKey(
            'name',
            $this->locksPlugin->getPluginInfo()
        );
    }

    public function testGetFeatures()
    {
        self::assertEquals([2], $this->locksPlugin->getFeatures());
    }

    public function testGetHTTPMethods()
    {
        self::assertEquals(['LOCK', 'UNLOCK'], $this->locksPlugin->getHTTPMethods(''));
    }

    public function testLockNoBody()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            ],
            $this->response->getHeaders()
         );

        self::assertEquals(400, $this->response->status);
    }

    public function testLock()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status, 'Got an incorrect status back. Response body: '.$this->response->getBodyAsString());

        $xml = $this->getSanitizedBodyAsXml();
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $elements = [
            '/d:prop',
            '/d:prop/d:lockdiscovery',
            '/d:prop/d:lockdiscovery/d:activelock',
            '/d:prop/d:lockdiscovery/d:activelock/d:locktype',
            '/d:prop/d:lockdiscovery/d:activelock/d:lockroot',
            '/d:prop/d:lockdiscovery/d:activelock/d:lockroot/d:href',
            '/d:prop/d:lockdiscovery/d:activelock/d:locktype/d:write',
            '/d:prop/d:lockdiscovery/d:activelock/d:lockscope',
            '/d:prop/d:lockdiscovery/d:activelock/d:lockscope/d:exclusive',
            '/d:prop/d:lockdiscovery/d:activelock/d:depth',
            '/d:prop/d:lockdiscovery/d:activelock/d:owner',
            '/d:prop/d:lockdiscovery/d:activelock/d:timeout',
            '/d:prop/d:lockdiscovery/d:activelock/d:locktoken',
            '/d:prop/d:lockdiscovery/d:activelock/d:locktoken/d:href',
        ];

        foreach ($elements as $elem) {
            $data = $xml->xpath($elem);
            self::assertEquals(1, count($data), 'We expected 1 match for the xpath expression "'.$elem.'". '.count($data).' were found. Full response body: '.$this->response->getBodyAsString());
        }

        $depth = $xml->xpath('/d:prop/d:lockdiscovery/d:activelock/d:depth');
        self::assertEquals('infinity', (string) $depth[0]);

        $token = $xml->xpath('/d:prop/d:lockdiscovery/d:activelock/d:locktoken/d:href');
        self::assertEquals($this->response->getHeader('Lock-Token'), '<'.(string) $token[0].'>', 'Token in response body didn\'t match token in response header.');
    }

    public function testLockWithContext()
    {
        $request = new HTTP\Request('LOCK', '/baseuri/test.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->setBaseUri('baseuri');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(200, $this->response->status, 'Got an incorrect status back. Response body: '.$this->response->getBodyAsString());

        $xml = $this->getSanitizedBodyAsXml();
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $lockRoot = $xml->xpath('/d:prop/d:lockdiscovery/d:activelock/d:lockroot/d:href');
        self::assertEquals('baseuri/test.txt', (string) $lockRoot[0]);
    }

    /**
     * @depends testLock
     */
    public function testDoubleLock()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
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

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;

        $this->server->exec();

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

        self::assertEquals(423, $this->response->status, 'Full response: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testLock
     */
    public function testLockRefresh()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
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

        $lockToken = $this->response->getHeader('Lock-Token');

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;

        $request = new HTTP\Request('LOCK', '/test.txt', ['If' => '('.$lockToken.')']);
        $request->setBody('');

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

        self::assertEquals(200, $this->response->status, 'We received an incorrect status code. Full response body: '.$this->response->getBody());
    }

    /**
     * @depends testLock
     */
    public function testLockRefreshBadToken()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
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

        $lockToken = $this->response->getHeader('Lock-Token');

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;

        $request = new HTTP\Request('LOCK', '/test.txt', ['If' => '('.$lockToken.'foobar) (<opaquelocktoken:anotherbadtoken>)']);
        $request->setBody('');

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

        self::assertEquals(423, $this->response->getStatus(), 'We received an incorrect status code. Full response body: '.$this->response->getBody());
    }

    /**
     * @depends testLock
     */
    public function testLockNoFile()
    {
        $request = new HTTP\Request('LOCK', '/notfound.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(201, $this->response->status);
    }

    /**
     * @depends testLock
     */
    public function testUnlockNoToken()
    {
        $request = new HTTP\Request('UNLOCK', '/test.txt');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            ],
            $this->response->getHeaders()
         );

        self::assertEquals(400, $this->response->status);
    }

    /**
     * @depends testLock
     */
    public function testUnlockBadToken()
    {
        $request = new HTTP\Request('UNLOCK', '/test.txt', ['Lock-Token' => '<opaquelocktoken:blablabla>']);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            ],
            $this->response->getHeaders()
         );

        self::assertEquals(409, $this->response->status, 'Got an incorrect status code. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testLock
     */
    public function testLockPutNoToken()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status);

        $request = new HTTP\Request('PUT', '/test.txt');
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(423, $this->response->status);
    }

    /**
     * @depends testLock
     */
    public function testUnlock()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
        $this->server->httpRequest = $request;

        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->invokeMethod($request, $this->server->httpResponse);
        $lockToken = $this->server->httpResponse->getHeader('Lock-Token');

        $request = new HTTP\Request('UNLOCK', '/test.txt', ['Lock-Token' => $lockToken]);
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->invokeMethod($request, $this->server->httpResponse);

        self::assertEquals(204, $this->server->httpResponse->status, 'Got an incorrect status code. Full response body: '.$this->response->getBodyAsString());
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length' => ['0'],
            ],
            $this->server->httpResponse->getHeaders()
         );
    }

    /**
     * @depends testLock
     */
    public function testUnlockWindowsBug()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
        $this->server->httpRequest = $request;

        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->invokeMethod($request, $this->server->httpResponse);
        $lockToken = $this->server->httpResponse->getHeader('Lock-Token');

        // See Issue 123
        $lockToken = trim($lockToken, '<>');

        $request = new HTTP\Request('UNLOCK', '/test.txt', ['Lock-Token' => $lockToken]);
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->invokeMethod($request, $this->server->httpResponse);

        self::assertEquals(204, $this->server->httpResponse->status, 'Got an incorrect status code. Full response body: '.$this->response->getBodyAsString());
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length' => ['0'],
            ],
            $this->server->httpResponse->getHeaders()
         );
    }

    /**
     * @depends testLock
     */
    public function testLockRetainOwner()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
        $this->server->httpRequest = $request;

        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>Evert</D:owner>
</D:lockinfo>');

        $this->server->invokeMethod($request, $this->server->httpResponse);
        $lockToken = $this->server->httpResponse->getHeader('Lock-Token');

        $locks = $this->locksPlugin->getLocks('test.txt');
        self::assertEquals(1, count($locks));
        self::assertEquals('Evert', $locks[0]->owner);
    }

    /**
     * @depends testLock
     */
    public function testLockPutBadToken()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status);

        $request = new HTTP\Request('PUT', '/test.txt', [
            'If' => '(<opaquelocktoken:token1>)',
        ]);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        // self::assertEquals('412 Precondition failed',$this->response->status);
        self::assertEquals(423, $this->response->status);
    }

    /**
     * @depends testLock
     */
    public function testLockDeleteParent()
    {
        $request = new HTTP\Request('LOCK', '/dir/child.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status);

        $request = new HTTP\Request('DELETE', '/dir');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(423, $this->response->status);
        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
    }

    /**
     * @depends testLock
     */
    public function testLockDeleteSucceed()
    {
        $request = new HTTP\Request('LOCK', '/dir/child.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status);

        $request = new HTTP\Request('DELETE', '/dir/child.txt', [
            'If' => '('.$this->response->getHeader('Lock-Token').')',
        ]);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(204, $this->response->status);
        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
    }

    /**
     * @depends testLock
     * Similar to testLockDeleteParent but don't lock the file but the Parent-DIR.
     */
    public function testParentLockDelete()
    {
        $request = new HTTP\Request('LOCK', '/dir/');
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

        self::assertEquals(200, $this->response->status);
        $lockToken = $this->response->getHeader('Lock-Token');

        $request = new HTTP\Request('DELETE', '/dir/child.txt', [
            'If' => '('.$lockToken.')',
        ]);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(204, $this->response->status);

        // verify that the LOCK on /dir/ itself continues to exist by unlocking:
        $request = new HTTP\Request('UNLOCK', '/dir/', ['Lock-Token' => $lockToken]);
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->invokeMethod($request, $this->server->httpResponse);

        self::assertEquals(204, $this->server->httpResponse->status, 'Got an incorrect status code. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testLock
     */
    public function testLockCopyLockSource()
    {
        $request = new HTTP\Request('LOCK', '/dir/child.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status);

        $request = new HTTP\Request('COPY', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(201, $this->response->status, 'Copy must succeed if only the source is locked, but not the destination');
        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
    }

    /**
     * @depends testLock
     */
    public function testLockCopyLockDestination()
    {
        $request = new HTTP\Request('LOCK', '/dir/child2.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(201, $this->response->status);

        $request = new HTTP\Request('COPY', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(423, $this->response->status, 'Copy must succeed if only the source is locked, but not the destination');
        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
    }

    /**
     * @depends testLock
     */
    public function testLockMoveLockSourceLocked()
    {
        $request = new HTTP\Request('LOCK', '/dir/child.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status);

        $request = new HTTP\Request('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(423, $this->response->status, 'Copy must succeed if only the source is locked, but not the destination');
        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
    }

    /**
     * @depends testLock
     */
    public function testLockMoveLockSourceSucceed()
    {
        $request = new HTTP\Request('LOCK', '/dir/child.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status);

        $request = new HTTP\Request('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
            'If' => '('.$this->response->getHeader('Lock-Token').')',
        ]);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(201, $this->response->status, 'A valid lock-token was provided for the source, so this MOVE operation must succeed. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testLock
     */
    public function testLockMoveLockDestination()
    {
        $request = new HTTP\Request('LOCK', '/dir/child2.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(201, $this->response->status);

        $request = new HTTP\Request('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(423, $this->response->status, 'Copy must succeed if only the source is locked, but not the destination');
        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
    }

    /**
     * @depends testLock
     */
    public function testLockMoveLockParent()
    {
        $request = new HTTP\Request('LOCK', '/dir', [
            'Depth' => 'infinite',
        ]);
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status);

        $request = new HTTP\Request('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
            'If' => '</dir> ('.$this->response->getHeader('Lock-Token').')',
        ]);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(201, $this->response->status, 'We locked the parent of both the source and destination, but the move didn\'t succeed.');
        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
    }

    /**
     * @depends testLock
     */
    public function testLockPutGoodToken()
    {
        $request = new HTTP\Request('LOCK', '/test.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(200, $this->response->status);

        $request = new HTTP\Request('PUT', '/test.txt', [
            'If' => '('.$this->response->getHeader('Lock-Token').')',
        ]);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(204, $this->response->status);
    }

    /**
     * @depends testLock
     */
    public function testLockPutUnrelatedToken()
    {
        $request = new HTTP\Request('LOCK', '/unrelated.txt');
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

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(201, $this->response->getStatus());

        $request = new HTTP\Request(
            'PUT',
            '/test.txt',
            ['If' => '</unrelated.txt> ('.$this->response->getHeader('Lock-Token').')']
        );
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        self::assertTrue(1 === preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')), 'We did not get a valid Locktoken back ('.$this->response->getHeader('Lock-Token').')');

        self::assertEquals(204, $this->response->status);
    }

    public function testPutWithIncorrectETag()
    {
        $request = new HTTP\Request('PUT', '/test.txt', [
            'If' => '(["etag1"])',
        ]);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();
        self::assertEquals(412, $this->response->status);
    }

    /**
     * @depends testPutWithIncorrectETag
     */
    public function testPutWithCorrectETag()
    {
        // We need an ETag-enabled file node.
        $tree = new DAV\Tree(new DAV\FSExt\Directory(SABRE_TEMPDIR));
        $this->server->tree = $tree;

        $filename = SABRE_TEMPDIR.'/test.txt';
        $etag = sha1(
            fileinode($filename).
            filesize($filename).
            filemtime($filename)
        );

        $request = new HTTP\Request('PUT', '/test.txt', [
            'If' => '(["'.$etag.'"])',
        ]);
        $request->setBody('newbody');

        $this->server->httpRequest = $request;
        $this->server->exec();
        self::assertEquals(204, $this->response->status, 'Incorrect status received. Full response body:'.$this->response->getBodyAsString());
    }

    public function testDeleteWithETagOnCollection()
    {
        $request = new HTTP\Request('DELETE', '/dir', [
            'If' => '(["etag1"])',
        ]);

        $this->server->httpRequest = $request;
        $this->server->exec();
        self::assertEquals(412, $this->response->status);
    }

    public function testGetTimeoutHeader()
    {
        $request = new HTTP\Request('LOCK', '/foo/bar', [
            'Timeout' => 'second-100',
        ]);

        $this->server->httpRequest = $request;
        self::assertEquals(100, $this->locksPlugin->getTimeoutHeader());
    }

    public function testGetTimeoutHeaderTwoItems()
    {
        $request = new HTTP\Request('LOCK', '/foo/bar', [
            'Timeout' => 'second-5, infinite',
        ]);
        $this->server->httpRequest = $request;
        self::assertEquals(5, $this->locksPlugin->getTimeoutHeader());
    }

    public function testGetTimeoutHeaderInfinite()
    {
        $request = new HTTP\Request('LOCK', '/foo/bar', [
            'Timeout' => 'infinite, second-5',
        ]);
        $this->server->httpRequest = $request;
        self::assertEquals(LockInfo::TIMEOUT_INFINITE, $this->locksPlugin->getTimeoutHeader());
    }

    public function testGetTimeoutHeaderInvalid()
    {
        $this->expectException(\Sabre\DAV\Exception\BadRequest::class);
        $request = new HTTP\Request('GET', '/', ['Timeout' => 'yourmom']);

        $this->server->httpRequest = $request;
        $this->locksPlugin->getTimeoutHeader();
    }
}
