<?php

declare(strict_types=1);

namespace Sabre\DAV\Sync;

use Sabre\DAV;
use Sabre\HTTP;

class PluginTest extends \Sabre\AbstractDAVServerTestCase
{
    protected $collection;

    public function setup(): void
    {
        parent::setUp();
        $this->server->addPlugin(new Plugin());
    }

    public function testGetInfo()
    {
        self::assertArrayHasKey(
            'name',
            (new Plugin())->getPluginInfo()
        );
    }

    public function setUpTree()
    {
        $this->collection =
            new MockSyncCollection('coll', [
                new DAV\SimpleFile('file1.txt', 'foo'),
                new DAV\SimpleFile('file2.txt', 'bar'),
            ]);
        $this->tree = [
            $this->collection,
            new DAV\SimpleCollection('normalcoll', []),
        ];
    }

    public function testSupportedReportSet()
    {
        $result = $this->server->getProperties('/coll', ['{DAV:}supported-report-set']);
        self::assertFalse($result['{DAV:}supported-report-set']->has('{DAV:}sync-collection'));

        // Making a change
        $this->collection->addChange(['file1.txt'], [], []);

        $result = $this->server->getProperties('/coll', ['{DAV:}supported-report-set']);
        self::assertTrue($result['{DAV:}supported-report-set']->has('{DAV:}sync-collection'));
    }

    public function testGetSyncToken()
    {
        $result = $this->server->getProperties('/coll', ['{DAV:}sync-token']);
        self::assertFalse(isset($result['{DAV:}sync-token']));

        // Making a change
        $this->collection->addChange(['file1.txt'], [], []);

        $result = $this->server->getProperties('/coll', ['{DAV:}sync-token']);
        self::assertTrue(isset($result['{DAV:}sync-token']));

        // non-sync-enabled collection
        $this->collection->addChange(['file1.txt'], [], []);

        $result = $this->server->getProperties('/normalcoll', ['{DAV:}sync-token']);
        self::assertFalse(isset($result['{DAV:}sync-token']));
    }

    public function testSyncInitialSyncCollection()
    {
        // Making a change
        $this->collection->addChange(['file1.txt'], [], []);

        $request = new HTTP\Request('REPORT', '/coll/', ['Content-Type' => 'application/xml']);

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

        self::assertEquals(207, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());

        $multiStatus = $this->server->xml->parse($response->getBodyAsString());

        // Checking the sync-token
        self::assertEquals(
            'http://sabre.io/ns/sync/1',
            $multiStatus->getSyncToken()
        );

        $responses = $multiStatus->getResponses();
        self::assertEquals(2, count($responses), 'We expected exactly 2 {DAV:}response');

        $response = $responses[0];

        self::assertNull($response->getHttpStatus());
        self::assertEquals('/coll/file1.txt', $response->getHref());
        self::assertEquals([
            200 => [
                '{DAV:}getcontentlength' => 3,
            ],
        ], $response->getResponseProperties());

        $response = $responses[1];

        self::assertNull($response->getHttpStatus());
        self::assertEquals('/coll/file2.txt', $response->getHref());
        self::assertEquals([
            200 => [
                '{DAV:}getcontentlength' => 3,
            ],
        ], $response->getResponseProperties());
    }

    public function testSubsequentSyncSyncCollection()
    {
        // Making a change
        $this->collection->addChange(['file1.txt'], [], []);
        // Making another change
        $this->collection->addChange([], ['file2.txt'], ['file3.txt']);

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/coll/',
            'CONTENT_TYPE' => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token>http://sabre.io/ns/sync/1</D:sync-token>
     <D:sync-level>infinite</D:sync-level>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        self::assertEquals(207, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());

        $multiStatus = $this->server->xml->parse($response->getBodyAsString());

        // Checking the sync-token
        self::assertEquals(
            'http://sabre.io/ns/sync/2',
            $multiStatus->getSyncToken()
        );

        $responses = $multiStatus->getResponses();
        self::assertEquals(2, count($responses), 'We expected exactly 2 {DAV:}response');

        $response = $responses[0];

        self::assertNull($response->getHttpStatus());
        self::assertEquals('/coll/file2.txt', $response->getHref());
        self::assertEquals([
            200 => [
                '{DAV:}getcontentlength' => 3,
            ],
        ], $response->getResponseProperties());

        $response = $responses[1];

        self::assertEquals('404', $response->getHttpStatus());
        self::assertEquals('/coll/file3.txt', $response->getHref());
        self::assertEquals([], $response->getResponseProperties());
    }

    public function testSubsequentSyncSyncCollectionLimit()
    {
        // Making a change
        $this->collection->addChange(['file1.txt'], [], []);
        // Making another change
        $this->collection->addChange([], ['file2.txt'], ['file3.txt']);

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/coll/',
            'CONTENT_TYPE' => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
    <D:sync-token>http://sabre.io/ns/sync/1</D:sync-token>
    <D:sync-level>infinite</D:sync-level>
    <D:prop>
        <D:getcontentlength/>
    </D:prop>
    <D:limit><D:nresults>1</D:nresults></D:limit>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        self::assertEquals(207, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());

        $multiStatus = $this->server->xml->parse(
            $response->getBodyAsString()
        );

        // Checking the sync-token
        self::assertEquals(
            'http://sabre.io/ns/sync/2',
            $multiStatus->getSyncToken()
        );

        $responses = $multiStatus->getResponses();
        self::assertEquals(2, count($responses), 'We expected exactly 2 {DAV:}responses');

        $response = $responses[0];

        self::assertEquals('404', $response->getHttpStatus());
        self::assertEquals('/coll/file3.txt', $response->getHref());
        self::assertEquals([], $response->getResponseProperties());

        $response = $responses[1];

        self::assertEquals('507', $response->getHttpStatus());
        self::assertEquals('/coll/', $response->getHref());
    }

    public function testSubsequentSyncSyncCollectionDepthFallBack()
    {
        // Making a change
        $this->collection->addChange(['file1.txt'], [], []);
        // Making another change
        $this->collection->addChange([], ['file2.txt'], ['file3.txt']);

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/coll/',
            'CONTENT_TYPE' => 'application/xml',
            'HTTP_DEPTH' => '1',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token>http://sabre.io/ns/sync/1</D:sync-token>
      <D:prop>
        <D:getcontentlength/>
      </D:prop>
</D:sync-collection>
BLA;

        $request->setBody($body);

        $response = $this->request($request);

        self::assertEquals(207, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());

        $multiStatus = $this->server->xml->parse(
            $response->getBodyAsString()
        );

        // Checking the sync-token
        self::assertEquals(
            'http://sabre.io/ns/sync/2',
            $multiStatus->getSyncToken()
        );

        $responses = $multiStatus->getResponses();
        self::assertEquals(2, count($responses), 'We expected exactly 2 {DAV:}response');

        $response = $responses[0];

        self::assertNull($response->getHttpStatus());
        self::assertEquals('/coll/file2.txt', $response->getHref());
        self::assertEquals([
            200 => [
                '{DAV:}getcontentlength' => 3,
            ],
        ], $response->getResponseProperties());

        $response = $responses[1];

        self::assertEquals('404', $response->getHttpStatus());
        self::assertEquals('/coll/file3.txt', $response->getHref());
        self::assertEquals([], $response->getResponseProperties());
    }

    public function testSyncNoSyncInfo()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/coll/',
            'CONTENT_TYPE' => 'application/xml',
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
        self::assertEquals(415, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());
    }

    public function testSyncNoSyncCollection()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/normalcoll/',
            'CONTENT_TYPE' => 'application/xml',
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
        self::assertEquals(415, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());
    }

    public function testSyncInvalidToken()
    {
        $this->collection->addChange(['file1.txt'], [], []);
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/coll/',
            'CONTENT_TYPE' => 'application/xml',
        ]);

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<D:sync-collection xmlns:D="DAV:">
     <D:sync-token>http://sabre.io/ns/sync/invalid</D:sync-token>
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
        self::assertEquals(403, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());
    }

    public function testSyncInvalidTokenNoPrefix()
    {
        $this->collection->addChange(['file1.txt'], [], []);
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/coll/',
            'CONTENT_TYPE' => 'application/xml',
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
        self::assertEquals(403, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());
    }

    public function testSyncNoSyncToken()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/coll/',
            'CONTENT_TYPE' => 'application/xml',
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
        self::assertEquals(400, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());
    }

    public function testSyncNoProp()
    {
        $this->collection->addChange(['file1.txt'], [], []);
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/coll/',
            'CONTENT_TYPE' => 'application/xml',
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
        self::assertEquals(400, $response->getStatus(), 'Full response body:'.$response->getBodyAsString());
    }

    public function testIfConditions()
    {
        $this->collection->addChange(['file1.txt'], [], []);
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/coll/file1.txt',
            'HTTP_IF' => '</coll> (<http://sabre.io/ns/sync/1>)',
        ]);
        $response = $this->request($request);

        // If a 403 is thrown this works correctly. The file in questions
        // doesn't allow itself to be deleted.
        // If the If conditions failed, it would have been a 412 instead.
        self::assertEquals(403, $response->getStatus());
    }

    public function testIfConditionsNot()
    {
        $this->collection->addChange(['file1.txt'], [], []);
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/coll/file1.txt',
            'HTTP_IF' => '</coll> (Not <http://sabre.io/ns/sync/2>)',
        ]);
        $response = $this->request($request);

        // If a 403 is thrown this works correctly. The file in questions
        // doesn't allow itself to be deleted.
        // If the If conditions failed, it would have been a 412 instead.
        self::assertEquals(403, $response->getStatus());
    }

    public function testIfConditionsNoSyncToken()
    {
        $this->collection->addChange(['file1.txt'], [], []);
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/coll/file1.txt',
            'HTTP_IF' => '</coll> (<opaquelocktoken:foo>)',
        ]);
        $response = $this->request($request);

        self::assertEquals(412, $response->getStatus());
    }
}
