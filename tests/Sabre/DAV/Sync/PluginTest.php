<?php

namespace Sabre\DAV\Sync;

use
    Sabre\DAV,
    Sabre\HTTP;

require_once __DIR__ . '/MockSyncCollection.php';

class PluginTest extends \Sabre\DAVServerTest {

    protected $collection;

    public function setUp() {

        parent::setUp();
        $this->server->addPlugin(new Plugin());

    }

    public function setUpTree() {

        $this->collection =
            new MockSyncCollection('coll', [
                new DAV\SimpleFile('file1.txt','foo'),
                new DAV\SimpleFile('file2.txt','bar'),
            ]);
        $this->tree = [
            $this->collection,
            new DAV\SimpleCollection('normalcoll', [])
        ];

    }

    public function testSupportedReportSet() {

        $result = $this->server->getProperties('/coll', ['{DAV:}supported-report-set']);
        $this->assertFalse($result['{DAV:}supported-report-set']->has('{DAV:}sync-collection'));

        // Making a change
        $this->collection->addChange(['file1.txt'], []);

        $result = $this->server->getProperties('/coll', ['{DAV:}supported-report-set']);
        $this->assertTrue($result['{DAV:}supported-report-set']->has('{DAV:}sync-collection'));

    }

    public function testGetSyncToken() {

        $result = $this->server->getProperties('/coll', ['{DAV:}sync-token']);
        $this->assertFalse(isset($result['{DAV:}sync-token']));

        // Making a change
        $this->collection->addChange(['file1.txt'], []);

        $result = $this->server->getProperties('/coll', ['{DAV:}sync-token']);
        $this->assertTrue(isset($result['{DAV:}sync-token']));

        // non-sync-enabled collection
        $this->collection->addChange(['file1.txt'], []);

        $result = $this->server->getProperties('/normalcoll', ['{DAV:}sync-token']);
        $this->assertFalse(isset($result['{DAV:}sync-token']));
    }

    public function testSyncInitialSyncCollection() {

        // Making a change
        $this->collection->addChange(['file1.txt'], []);

        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'    => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token/>
     <D:sync-level>1</D:sync-level>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 207 Multi-Status', $response->status, 'Full response body:' . $response->body);

        $dom = DAV\XMLUtil::loadDOMDocument(
            $response->body
        );

        // Checking the sync-token
        $this->assertEquals(
            'http://sabredav.org/ns/sync/1',
            $dom->getElementsByTagNameNS('urn:DAV', 'sync-token')->item(0)->nodeValue
        );

        $responses = DAV\Property\ResponseList::unserialize(
            $dom->documentElement,
            []
        );

        $responses = $responses->getResponses();
        $this->assertEquals(2, count($responses), 'We expected exactly 2 {DAV:}response');

        $response = $responses[0];

        $this->assertEquals('200', $response->getHttpStatus());
        $this->assertEquals('/coll/file1.txt', $response->getHref());
        $this->assertEquals([
            200 => [
                '{DAV:}getcontentlength' => 3,
            ]
        ], $response->getResponseProperties());

        $response = $responses[1];

        $this->assertEquals('200', $response->getHttpStatus());
        $this->assertEquals('/coll/file2.txt', $response->getHref());
        $this->assertEquals([
            200 => [
                '{DAV:}getcontentlength' => 3,
            ]
        ], $response->getResponseProperties());

    }

    public function testSubsequentSyncSyncCollection() {

        // Making a change
        $this->collection->addChange(['file1.txt'], []);
        // Making another change
        $this->collection->addChange(['file2.txt'], ['file3.txt']);

        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'    => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token>http://sabredav.org/ns/sync/1</D:sync-token>
     <D:sync-level>infinite</D:sync-level>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 207 Multi-Status', $response->status, 'Full response body:' . $response->body);

        $dom = DAV\XMLUtil::loadDOMDocument(
            $response->body
        );

        // Checking the sync-token
        $this->assertEquals(
            'http://sabredav.org/ns/sync/2',
            $dom->getElementsByTagNameNS('urn:DAV', 'sync-token')->item(0)->nodeValue
        );

        $responses = DAV\Property\ResponseList::unserialize(
            $dom->documentElement,
            []
        );

        $responses = $responses->getResponses();
        $this->assertEquals(2, count($responses), 'We expected exactly 2 {DAV:}response');

        $response = $responses[0];

        $this->assertEquals('200', $response->getHttpStatus());
        $this->assertEquals('/coll/file2.txt', $response->getHref());
        $this->assertEquals([
            200 => [
                '{DAV:}getcontentlength' => 3,
            ]
        ], $response->getResponseProperties());

        $response = $responses[1];

        $this->assertEquals('404', $response->getHttpStatus());
        $this->assertEquals('/coll/file3.txt', $response->getHref());
        $this->assertEquals([], $response->getResponseProperties());

    }

    public function testSubsequentSyncSyncCollectionLimit() {

        // Making a change
        $this->collection->addChange(['file1.txt'], []);
        // Making another change
        $this->collection->addChange(['file2.txt'], ['file3.txt']);

        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'    => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
    <D:sync-token>http://sabredav.org/ns/sync/1</D:sync-token>
    <D:sync-level>infinite</D:sync-level>
    <D:prop>
        <D:getcontentlength/>
    </D:prop>
    <D:limit><D:nresults>1</D:nresults></D:limit>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 207 Multi-Status', $response->status, 'Full response body:' . $response->body);

        $dom = DAV\XMLUtil::loadDOMDocument(
            $response->body
        );

        // Checking the sync-token
        $this->assertEquals(
            'http://sabredav.org/ns/sync/2',
            $dom->getElementsByTagNameNS('urn:DAV', 'sync-token')->item(0)->nodeValue
        );

        $responses = DAV\Property\ResponseList::unserialize(
            $dom->documentElement,
            []
        );

        $responses = $responses->getResponses();
        $this->assertEquals(1, count($responses), 'We expected exactly 1 {DAV:}response');

        $response = $responses[0];

        $this->assertEquals('404', $response->getHttpStatus());
        $this->assertEquals('/coll/file3.txt', $response->getHref());
        $this->assertEquals([], $response->getResponseProperties());

    }

    public function testSubsequentSyncSyncCollectionDepthFallBack() {

        // Making a change
        $this->collection->addChange(['file1.txt'], []);
        // Making another change
        $this->collection->addChange(['file2.txt'], ['file3.txt']);

        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'   => 'application/xml',
            'HTTP_DEPTH'     => "1",
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token>http://sabredav.org/ns/sync/1</D:sync-token>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        $this->assertEquals('HTTP/1.1 207 Multi-Status', $response->status, 'Full response body:' . $response->body);

        $dom = DAV\XMLUtil::loadDOMDocument(
            $response->body
        );

        // Checking the sync-token
        $this->assertEquals(
            'http://sabredav.org/ns/sync/2',
            $dom->getElementsByTagNameNS('urn:DAV', 'sync-token')->item(0)->nodeValue
        );

        $responses = DAV\Property\ResponseList::unserialize(
            $dom->documentElement,
            []
        );

        $responses = $responses->getResponses();
        $this->assertEquals(2, count($responses), 'We expected exactly 2 {DAV:}response');

        $response = $responses[0];

        $this->assertEquals('200', $response->getHttpStatus());
        $this->assertEquals('/coll/file2.txt', $response->getHref());
        $this->assertEquals([
            200 => [
                '{DAV:}getcontentlength' => 3,
            ]
        ], $response->getResponseProperties());

        $response = $responses[1];

        $this->assertEquals('404', $response->getHttpStatus());
        $this->assertEquals('/coll/file3.txt', $response->getHref());
        $this->assertEquals([], $response->getResponseProperties());

    }

    public function testSyncNoSyncInfo() {

        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'    => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token/>
     <D:sync-level>1</D:sync-level>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        // The default state has no sync-token, so this report should not yet
        // be supported.
        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status, 'Full response body:' . $response->body);

    }

    public function testSyncNoSyncCollection() {

        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/normalcoll/',
            'CONTENT_TYPE'    => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token/>
     <D:sync-level>1</D:sync-level>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        // The default state has no sync-token, so this report should not yet
        // be supported.
        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status, 'Full response body:' . $response->body);

    }

    public function testSyncInvalidToken() {

        $this->collection->addChange(['file1.txt'], []);
        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'    => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token>http://sabredav.org/ns/sync/invalid</D:sync-token>
     <D:sync-level>1</D:sync-level>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        // The default state has no sync-token, so this report should not yet
        // be supported.
        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status, 'Full response body:' . $response->body);

    }
    public function testSyncInvalidTokenNoPrefix() {

        $this->collection->addChange(['file1.txt'], []);
        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'    => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token>invalid</D:sync-token>
     <D:sync-level>1</D:sync-level>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        // The default state has no sync-token, so this report should not yet
        // be supported.
        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status, 'Full response body:' . $response->body);

    }

    public function testSyncNoSyncToken() {

        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'    => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-level>1</D:sync-level>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        // The default state has no sync-token, so this report should not yet
        // be supported.
        $this->assertEquals('HTTP/1.1 400 Bad request', $response->status, 'Full response body:' . $response->body);

    }

    public function testSyncInvalidDepthValue() {

        $this->collection->addChange(['file1.txt'], []);
        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'   => 'application/xml',
            'HTTP_DEPTH'     => "1",
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token />
     <D:sync-level>1</D:sync-level>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        // The default state has no sync-token, so this report should not yet
        // be supported.
        $this->assertEquals('HTTP/1.1 403 Forbidden', $response->status, 'Full response body:' . $response->body);

    }

    public function testSyncNoProp() {

        $this->collection->addChange(['file1.txt'], []);
        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'   => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token />
     <D:sync-level>1</D:sync-level>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        // The default state has no sync-token, so this report should not yet
        // be supported.
        $this->assertEquals('HTTP/1.1 400 Bad request', $response->status, 'Full response body:' . $response->body);

    }
}
