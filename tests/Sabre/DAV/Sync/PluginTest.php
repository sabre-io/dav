<?php

namespace Sabre\DAV\Sync;

use
    Sabre\DAV,
    Sabre\HTTP;

require_once __DIR__ . '/MockSyncCollection.php';

class PluginTest extends \Sabre\DAVServerTest {

    protected $collection;

    public function setUpTree() {

        $this->collection =
            new MockSyncCollection('coll', [
                new DAV\SimpleFile('file1.txt','foobar'),
            ]);
        $this->tree = [
            $this->collection,
            new DAV\SimpleCollection('normal collection', [])
        ];

    }

    public function testSyncCollection() {

        $request = new HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/coll/',
            'CONTENT_TYPE'    => 'application/xml',
        ]);

        $body = <<<BLA


BLA;

        $this->markTestIncomplete('Not done yet');

    }

}
