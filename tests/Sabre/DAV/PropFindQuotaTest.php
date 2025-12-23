<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\AbstractDAVServerTestCase;
use Sabre\Http;

class PropFindQuotaTest extends AbstractDAVServerTestCase
{
    /**
     * Sets up the DAV tree.
     */
    public function setUpTree()
    {
        $this->tree = new Mock\QuotaCollection('root', [
            'file1' => 'foo',
            new Mock\Collection('dir', []),
        ]);
    }

    public function testPropFindAllprops()
    {
        $request = new HTTP\Request('PROPFIND', '/', ['Depth' => 0]);
        $response = $this->request($request);

        $xml = $response->getBodyAsString();

        self::assertSame(207, $response->getStatus());
        self::assertTrue(false === strpos($xml, 'quota-used-bytes'));
        self::assertTrue(false === strpos($xml, 'quota-available-bytes'));
    }

    public function testPropFindQuotaProps()
    {
        $this->tree->available = 123456789;
        $this->tree->used = 98765;

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:quota-used-bytes />
    <d:quota-available-bytes />
  </d:prop>
</d:propfind>';

        $request = new HTTP\Request('PROPFIND', '/', ['Depth' => 0], $xml);
        $response = $this->request($request);

        $xml = $response->getBodyAsString();

        self::assertSame(207, $response->getStatus());
        self::assertTrue(strpos($xml, '<d:quota-used-bytes>98765</d:quota-used-bytes>') > 0);
        self::assertTrue(strpos($xml, '<d:quota-available-bytes>123456789</d:quota-available-bytes>') > 0);
    }

    public function testPropFindQuotaPropsNotFound()
    {
        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:quota-used-bytes />
    <d:quota-available-bytes />
  </d:prop>
</d:propfind>';

        // Requested location does not implement iQuota interface
        $request = new HTTP\Request('PROPFIND', '/dir', ['Depth' => 0], $xml);
        $response = $this->request($request);

        $xml = $response->getBodyAsString();

        self::assertSame(207, $response->getStatus());
        $expected = '<d:propstat><d:prop><d:quota-used-bytes/><d:quota-available-bytes/></d:prop>'
            .'<d:status>HTTP/1.1 404 Not Found</d:status></d:propstat>';
        self::assertTrue(strpos($xml, $expected) > 0);
    }
}
