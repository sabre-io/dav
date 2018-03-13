<?php declare (strict_types=1);

namespace Sabre\DAV\Locks;

use GuzzleHttp\Psr7\ServerRequest;
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

        $request = new ServerRequest('LOCK', '/test.txt');

        $response = $this->server->handle($request);
        $this->assertEquals(400, $response->getStatusCode(), $response->getBody()->getContents());

        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $response->getHeaders());
    }

    function testLock() {
        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), 'Got an incorrect status back. Response body: ' . $responseBody);
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');



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

        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->handle($request);


        $response = $this->server->handle($request);

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $this->assertEquals(423, $response->getStatusCode(), 'Full response: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockRefresh() {

        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);

        $lockToken = $response->getHeaderLine('Lock-Token');

        $request = new ServerRequest('LOCK', '/test.txt', ['If' => '(' . $lockToken . ')'], '');

        $response = $this->server->handle($request);

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $this->assertEquals(200, $response->getStatusCode(), 'We received an incorrect status code. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockRefreshBadToken() {

        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        
        $lockToken = $response->getHeaderLine('Lock-Token');

        $request = new ServerRequest('LOCK', '/test.txt', ['If' => '(' . $lockToken . 'foobar) (<opaquelocktoken:anotherbadtoken>)'], '');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $this->assertEquals(423, $response->getStatusCode(), 'We received an incorrect status code. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockNoFile() {

        $request = new ServerRequest('LOCK', '/notfound.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(201, $response->getStatusCode());

    }

    /**
     * @depends testLock
     */
    function testUnlockNoToken() {

        $request = new ServerRequest('UNLOCK', '/test.txt');
        $response = $this->server->handle($request);
        

        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $response->getHeaders()
         );

        $this->assertEquals(400, $response->getStatusCode());

    }

    /**
     * @depends testLock
     */
    function testUnlockBadToken() {

        $request = new ServerRequest('UNLOCK', '/test.txt', ['Lock-Token' => '<opaquelocktoken:blablabla>']);
        $response = $this->server->handle($request);
        

        $this->assertEquals([

            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $response->getHeaders()
         );

        $this->assertEquals(409, $response->getStatusCode(), 'Got an incorrect status code. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockPutNoToken() {

        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());

        $request = new ServerRequest('PUT', '/test.txt', [], 'newbody');
        $response = $this->server->handle($request);

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

        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        $lockToken = $response->getHeaderLine('Lock-Token');

        $request = new ServerRequest('UNLOCK', '/test.txt', ['Lock-Token' => $lockToken]);
        $response = $this->server->handle($request);
        $responseBody = $response->getBody()->getContents();
        $this->assertEquals(204, $response->getStatusCode(), 'Got an incorrect status code. Full response body: ' . $responseBody);
        $this->assertEquals([

            'Content-Length'  => ['0'],
            ],
            $response->getHeaders()
         , print_r($response->getHeaders(), true));
    }

    /**
     * @depends testLock
     */
    function testUnlockWindowsBug() {

        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        $lockToken = $response->getHeaderLine('Lock-Token');

        // See Issue 123
        $lockToken = trim($lockToken, '<>');

        $request = new ServerRequest('UNLOCK', '/test.txt', ['Lock-Token' => $lockToken]);
        $response = $this->server->handle($request);
        
        $this->assertEquals(204, $response->getStatusCode(), 'Got an incorrect status code. Full response body: ' . $response->getBody()->getContents());
        $this->assertEquals([

            'Content-Length'  => ['0'],
            ],
            $response->getHeaders()
         );


    }

    /**
     * @depends testLock
     */
    function testLockRetainOwner() {

        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>Evert</D:owner>
</D:lockinfo>');
        $response = $this->server->handle($request);

        $lockToken = $response->getHeaderLine('Lock-Token');

        $locks = $this->locksPlugin->getLocks('test.txt');
        $this->assertEquals(1, count($locks));
        $this->assertEquals('Evert', $locks[0]->owner);


    }

    /**
     * @depends testLock
     */
    function testLockPutBadToken() {

        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());

        $request = new ServerRequest('PUT', '/test.txt', [
            'If' => '(<opaquelocktoken:token1>)',
        ], 'newbody');

        $response = $this->server->handle($request);
        $this->assertEquals(423, $response->getStatusCode());
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

//        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');
        // $this->assertEquals('412 Precondition failed',$response->getStatusCode());


    }

    /**
     * @depends testLock
     */
    function testLockDeleteParent() {

        $request = new ServerRequest('LOCK', '/dir/child.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());

        $request = new ServerRequest('DELETE', '/dir');
        $response = $this->server->handle($request);
        

        $this->assertEquals(423, $response->getStatusCode());
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

    }
    /**
     * @depends testLock
     */
    function testLockDeleteSucceed() {

        $request = new ServerRequest('LOCK', '/dir/child.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());

        $request = new ServerRequest('DELETE', '/dir/child.txt', [
            'If' => '(' . $response->getHeaderLine('Lock-Token') . ')',
        ]);
        $response = $this->server->handle($request);
        

        $this->assertEquals(204, $response->getStatusCode());
//        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockCopyLockSource() {

        $request = new ServerRequest('LOCK', '/dir/child.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());

        $request = new ServerRequest('COPY', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt'
        ]);

        $response = $this->server->handle($request);
        $this->assertEquals(201, $response->getStatusCode(), 'Copy must succeed if only the source is locked, but not the destination');

//        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'), $response->getBody()->getContents());

    }
    /**
     * @depends testLock
     */
    function testLockCopyLockDestination() {

        $request = new ServerRequest('LOCK', '/dir/child2.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(201, $response->getStatusCode());

        $request = new ServerRequest('COPY', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);
        $response = $this->server->handle($request);
        

        $this->assertEquals(423, $response->getStatusCode(), 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockMoveLockSourceLocked() {

        $request = new ServerRequest('LOCK', '/dir/child.txt', [],'<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());

        $request = new ServerRequest('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);
        $response = $this->server->handle($request);
        

        $this->assertEquals(423, $response->getStatusCode(), 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockMoveLockSourceSucceed() {

        $request = new ServerRequest('LOCK', '/dir/child.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());


        $request = new ServerRequest('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
            'If'          => '(' . $response->getHeaderLine('Lock-Token') . ')',
        ]);
        $response = $this->server->handle($request);
        

        $this->assertEquals(201, $response->getStatusCode(), 'A valid lock-token was provided for the source, so this MOVE operation must succeed. Full response body: ' . $response->getBody()->getContents());

    }

    /**
     * @depends testLock
     */
    function testLockMoveLockDestination() {

        $request = new ServerRequest('LOCK', '/dir/child2.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(201, $response->getStatusCode());

        $request = new ServerRequest('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
        ]);
        $response = $this->server->handle($request);
        

        $this->assertEquals(423, $response->getStatusCode(), 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

    }
    /**
     * @depends testLock
     */
    function testLockMoveLockParent() {

        $request = new ServerRequest('LOCK', '/dir', [
            'Depth' => 'infinite',
        ], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());

        $request = new ServerRequest('MOVE', '/dir/child.txt', [
            'Destination' => '/dir/child2.txt',
            'If'          => '</dir> (' . $response->getHeaderLine('Lock-Token') . ')',
        ]);
        $response = $this->server->handle($request);
        

        $this->assertEquals(201, $response->getStatusCode(), 'We locked the parent of both the source and destination, but the move didn\'t succeed.');
//        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockPutGoodToken() {

        $request = new ServerRequest('LOCK', '/test.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(200, $response->getStatusCode());

        $request = new ServerRequest('PUT', '/test.txt', [
            'If' => '(' . $response->getHeaderLine('Lock-Token') . ')',
        ], 'newbody');
        $response = $this->server->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
//        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
//        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');



    }

    /**
     * @depends testLock
     */
    function testLockPutUnrelatedToken() {

        $request = new ServerRequest('LOCK', '/unrelated.txt', [], '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $response = $this->server->handle($request);
        

        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');

        $this->assertEquals(201, $response->getStatusCode());

        $request = new ServerRequest(
            'PUT',
            '/test.txt',
            ['If' => '</unrelated.txt> (' . $response->getHeaderLine('Lock-Token') . ')'],
            'newbody'
        );
        $response = $this->server->handle($request);


        $this->assertEquals(204, $response->getStatusCode());
//        $this->assertEquals('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));
//        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $response->getHeaderLine('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $response->getHeaderLine('Lock-Token') . ')');


    }

    function testPutWithIncorrectETag() {

        $request = new ServerRequest('PUT', '/test.txt', [
            'If' => '(["etag1"])', 'newbody'
        ]);
        $response = $this->server->handle($request);
        $this->assertEquals(412, $response->getStatusCode());

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

        $request = new ServerRequest('PUT', '/test.txt', [
            'If' => '(["' . $etag . '"])', 'newbody'
        ]);

        $response = $this->server->handle($request);
        $this->assertEquals(204, $response->getStatusCode(), 'Incorrect status received. Full response body:' . $response->getBody()->getContents());
    }

    function testDeleteWithETagOnCollection() {

        $request = new ServerRequest('DELETE', '/dir', [
            'If' => '(["etag1"])',
        ]);

        $response = $this->server->handle($request);
        $this->assertEquals(412, $response->getStatusCode());

    }

    function testGetTimeoutHeader() {

        $request = new ServerRequest('LOCK', '/foo/bar', [
            'Timeout' => 'second-100',
        ]);

        $this->server->handle($request);
        $this->assertEquals(100, $this->locksPlugin->getTimeoutHeader());

    }

    function testGetTimeoutHeaderTwoItems() {

        $request = new ServerRequest('LOCK', '/foo/bar', [
            'Timeout' => 'second-5, infinite',
        ]);
        $this->server->handle($request);
        $this->assertEquals(5, $this->locksPlugin->getTimeoutHeader());

    }

    function testGetTimeoutHeaderInfinite() {

        $request = new ServerRequest('LOCK', '/foo/bar', [
            'Timeout' => 'infinite, second-5',
        ]);
        $this->server->handle($request);
        $this->assertEquals(LockInfo::TIMEOUT_INFINITE, $this->locksPlugin->getTimeoutHeader());

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testGetTimeoutHeaderInvalid() {

        $request = new ServerRequest('GET', '/', ['Timeout' => 'yourmom']);
        $this->server->handle($request);
        $this->locksPlugin->getTimeoutHeader();

    }


}
