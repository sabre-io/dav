<?php declare (strict_types=1);

namespace Sabre\DAV\Locks;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer {

    /**
     * @var Plugin
     */
    protected $locksPlugin;

    function setUp() {

        parent::setUp();
        $locksBackend = new Backend\File(SABRE_TEMPDIR . '/locksdb');
        $locksPlugin = new Plugin($locksBackend);
        $this->server->addPlugin($locksPlugin);
        $this->locksPlugin = $locksPlugin;

    }

    function testGetInfo() {

        $this->assertArrayHasKey(
            'name',
            $this->locksPlugin->getPluginInfo()
        );

    }

    function testGetFeatures() {

        $this->assertEquals([2], $this->locksPlugin->getFeatures());

    }

    function testGetHTTPMethods() {

        $this->assertEquals(['LOCK', 'UNLOCK'], $this->locksPlugin->getHTTPMethods(''));

    }

    function testLockNoBody() {

        $request = new HTTP\Request('LOCK', '/test.txt');
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $this->getResponse()->getHeaders()
         );

        $this->assertEquals(400, $this->getResponse()->getStatusCode());

    }

    function testLock() {

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
        $this->server->start();
        $response = $this->server->httpResponse->getResponse();
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode(), 'Got an incorrect status back. Response body: ' . $responseBody);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $responseBody);
        $xml = simplexml_load_string($body);
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
            $this->assertEquals(1, count($data), 'We expected 1 match for the xpath expression "' . $elem . '". ' . count($data) . ' were found. Full response body: ' . $responseBody);
        }

        $depth = $xml->xpath('/d:prop/d:lockdiscovery/d:activelock/d:depth');
        $this->assertEquals('infinity', (string)$depth[0]);

        $token = $xml->xpath('/d:prop/d:lockdiscovery/d:activelock/d:locktoken/d:href');
        $this->assertEquals($response->getHeaderLine('Lock-Token'), '<' . (string)$token[0] . '>', 'Token in response body didn\'t match token in response header.');

    }

    /**
     * @depends testLock
     */
    function testDoubleLock() {

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
        $this->server->start();
        $this->server->start();
        $response = $this->server->httpResponse->getResponse();

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $this->assertEquals(423, $response->getStatusCode(), 'Full response: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockRefresh() {

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
        $this->server->start();

        $lockToken = $this->getResponse()->getHeaderLine('Lock-Token');

        $request = new HTTP\Request('LOCK', '/test.txt', ['If' => '(' . $lockToken . ')']);
        $request->setBody('');

        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));

        $this->assertEquals(200, $this->getResponse()->getStatusCode(), 'We received an incorrect status code. Full response body: ' . $this->getResponse()->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockRefreshBadToken() {

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
        $this->server->start();

        $lockToken = $this->getResponse()->getHeaderLine('Lock-Token');

        $request = new HTTP\Request('LOCK', '/test.txt', ['If' => '(' . $lockToken . 'foobar) (<opaquelocktoken:anotherbadtoken>)']);
        $request->setBody('');

        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));

        $this->assertEquals(423, $this->getResponse()->getStatusCode(), 'We received an incorrect status code. Full response body: ' . $this->getResponse()->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockNoFile() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(201, $this->getResponse()->getStatusCode());

    }

    /**
     * @depends testLock
     */
    function testUnlockNoToken() {

        $request = new HTTP\Request('UNLOCK', '/test.txt');
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $this->getResponse()->getHeaders()
         );

        $this->assertEquals(400, $this->getResponse()->getStatusCode());

    }

    /**
     * @depends testLock
     */
    function testUnlockBadToken() {

        $request = new HTTP\Request('UNLOCK', '/test.txt', ['Lock-Token' => '<opaquelocktoken:blablabla>']);
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $this->getResponse()->getHeaders()
         );

        $this->assertEquals(409, $this->getResponse()->getStatusCode(), 'Got an incorrect status code. Full response body: ' . $this->getResponse()->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockPutNoToken() {

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
        $this->server->start();
        $response = $this->server->httpResponse->getResponse();

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());

        $request = new HTTP\Request('PUT', '/test.txt');
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->httpResponse->reset();
        $this->server->start();
        $response = $this->server->httpResponse->getResponse();
        $this->assertEquals(423, $response->getStatusCode());
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

        /**
         * This assertion does not make sense.
         *
         */
//        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');



    }

    /**
     * @depends testLock
     */
    function testUnlock() {

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
        $response = $this->server->httpResponse->getResponse();
        $lockToken = $response->getHeaderLine('Lock-Token');

        $request = new HTTP\Request('UNLOCK', '/test.txt', ['Lock-Token' => $lockToken]);
        $this->server->httpRequest = $request;
        $this->server->httpResponse->reset();
        $this->server->invokeMethod($request, $this->server->httpResponse);

        $response = $this->server->httpResponse->getResponse();
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(204, $response->getStatusCode(), 'Got an incorrect status code. Full response body: ' . $responseBody);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
            ],
            $response->getHeaders()
         , print_r($response->getHeaders(), true));
    }

    /**
     * @depends testLock
     */
    function testUnlockWindowsBug() {

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
        $this->server->httpResponse->reset();
        $this->server->invokeMethod($request, $this->server->httpResponse);

        $this->assertEquals(204, $this->getResponse()->getStatusCode(), 'Got an incorrect status code. Full response body: ' . $this->getResponse()->getBody()->getContents());
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
            ],
            $this->getResponse()->getHeaders()
         );


    }

    /**
     * @depends testLock
     */
    function testLockRetainOwner() {

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
        $this->assertEquals(1, count($locks));
        $this->assertEquals('Evert', $locks[0]->owner);


    }

    /**
     * @depends testLock
     */
    function testLockPutBadToken() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request('PUT', '/test.txt', [
            'If' => '(<opaquelocktoken:token1>)',
        ]);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        // $this->assertEquals('412 Precondition failed',$this->getResponse()->getStatusCode());
        $this->assertEquals(423, $this->getResponse()->getStatusCode());

    }

    /**
     * @depends testLock
     */
    function testLockDeleteParent() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request('DELETE', '/dir');
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(423, $this->getResponse()->getStatusCode());
        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));

    }
    /**
     * @depends testLock
     */
    function testLockDeleteSucceed() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request('DELETE', '/dir/child.txt', [
            'If' => '(' . $this->getResponse()->getHeaderLine('Lock-Token') . ')',
        ]);
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(204, $this->getResponse()->getStatusCode());
        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockCopyLockSource() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request('COPY', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt'
        ]);

        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(201, $this->getResponse()->getStatusCode(), 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));

    }
    /**
     * @depends testLock
     */
    function testLockCopyLockDestination() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(201, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request('COPY', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(423, $this->getResponse()->getStatusCode(), 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockMoveLockSourceLocked() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(423, $this->getResponse()->getStatusCode(), 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockMoveLockSourceSucceed() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());


        $request = new HTTP\Request('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
            'If'          => '(' . $this->getResponse()->getHeaderLine('Lock-Token') . ')',
        ]);
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(201, $this->getResponse()->getStatusCode(), 'A valid lock-token was provided for the source, so this MOVE operation must succeed. Full response body: ' . $this->getResponse()->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockMoveLockDestination() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(201, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(423, $this->getResponse()->getStatusCode(), 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));

    }
    /**
     * @depends testLock
     */
    function testLockMoveLockParent() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
            'If'          => '</dir> (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')',
        ]);
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals(201, $this->getResponse()->getStatusCode(), 'We locked the parent of both the source and destination, but the move didn\'t succeed.');
        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockPutGoodToken() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request('PUT', '/test.txt', [
            'If' => '(' . $this->getResponse()->getHeaderLine('Lock-Token') . ')',

        ]);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(204, $this->getResponse()->getStatusCode());

    }

    /**
     * @depends testLock
     */
    function testLockPutUnrelatedToken() {

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
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(201, $this->getResponse()->getStatusCode());

        $request = new HTTP\Request(
            'PUT',
            '/test.txt',
            ['If' => '</unrelated.txt> (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')']
        );
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->start();

        $this->assertEquals('application/xml; charset=utf-8', $this->getResponse()->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->getResponse()->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->getResponse()->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(204, $this->getResponse()->getStatusCode());

    }

    function testPutWithIncorrectETag() {

        $request = new HTTP\Request('PUT', '/test.txt', [
            'If' => '(["etag1"])',
        ]);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->start();
        $this->assertEquals(412, $this->getResponse()->getStatusCode());

    }

    /**
     * @depends testPutWithIncorrectETag
     */
    function testPutWithCorrectETag() {

        // We need an ETag-enabled file node.
        $tree = new DAV\Tree(new DAV\FSExt\Directory(SABRE_TEMPDIR));
        $this->server->tree = $tree;

        $filename = SABRE_TEMPDIR . '/test.txt';
        $etag = sha1(
            fileinode($filename) .
            filesize($filename) .
            filemtime($filename)
        );

        $request = new HTTP\Request('PUT', '/test.txt', [
            'If' => '(["' . $etag . '"])',
        ]);
        $request->setBody('newbody');

        $this->server->httpRequest = $request;
        $this->server->start();
        $this->assertEquals(204, $this->getResponse()->getStatusCode(), 'Incorrect status received. Full response body:' . $this->getResponse()->getBody()->getContents());

    }

    function testDeleteWithETagOnCollection() {

        $request = new HTTP\Request('DELETE', '/dir', [
            'If' => '(["etag1"])',
        ]);

        $this->server->httpRequest = $request;
        $this->server->start();
        $this->assertEquals(412, $this->getResponse()->getStatusCode());

    }

    function testGetTimeoutHeader() {

        $request = new HTTP\Request('LOCK', '/foo/bar', [
            'Timeout' => 'second-100',
        ]);

        $this->server->httpRequest = $request;
        $this->assertEquals(100, $this->locksPlugin->getTimeoutHeader());

    }

    function testGetTimeoutHeaderTwoItems() {

        $request = new HTTP\Request('LOCK', '/foo/bar', [
            'Timeout' => 'second-5, infinite',
        ]);
        $this->server->httpRequest = $request;
        $this->assertEquals(5, $this->locksPlugin->getTimeoutHeader());

    }

    function testGetTimeoutHeaderInfinite() {

        $request = new HTTP\Request('LOCK', '/foo/bar', [
            'Timeout' => 'infinite, second-5',
        ]);
        $this->server->httpRequest = $request;
        $this->assertEquals(LockInfo::TIMEOUT_INFINITE, $this->locksPlugin->getTimeoutHeader());

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testGetTimeoutHeaderInvalid() {

        $request = new HTTP\Request('GET', '/', ['Timeout' => 'yourmom']);

        $this->server->httpRequest = $request;
        $this->locksPlugin->getTimeoutHeader();

    }


}
