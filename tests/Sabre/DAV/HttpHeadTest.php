<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\AbstractDAVServerTestCase;
use Sabre\HTTP;

/**
 * Tests related to the HEAD request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HttpHeadTest extends AbstractDAVServerTestCase
{
    /**
     * Sets up the DAV tree.
     */
    public function setUpTree()
    {
        $this->tree = new Mock\Collection('root', [
            'file1' => 'foo',
            new Mock\Collection('dir', []),
            new Mock\StreamingFile('streaming', 'stream'),
        ]);
    }

    public function testHEAD()
    {
        $request = new HTTP\Request('HEAD', '//file1');
        $response = $this->request($request);

        self::assertEquals(200, $response->getStatus());

        // Removing Last-Modified because it keeps changing.
        $response->removeHeader('Last-Modified');

        self::assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type' => ['application/octet-stream'],
                'Content-Length' => [3],
                'ETag' => ['"'.md5('foo').'"'],
            ],
            $response->getHeaders()
        );

        self::assertEquals('', $response->getBodyAsString());
    }

    /**
     * According to the specs, HEAD should behave identical to GET. But, broken
     * clients needs HEAD requests on collections to respond with a 200, so
     * that's what we do.
     */
    public function testHEADCollection()
    {
        $request = new HTTP\Request('HEAD', '/dir');
        $response = $this->request($request);

        self::assertEquals(200, $response->getStatus());
    }

    /**
     * HEAD automatically internally maps to GET via a sub-request.
     * The Auth plugin must not be triggered twice for these, so we'll
     * test for that.
     */
    public function testDoubleAuth()
    {
        $count = 0;

        $authBackend = new Auth\Backend\BasicCallBack(function ($userName, $password) use (&$count) {
            ++$count;

            return true;
        });
        $this->server->addPlugin(
            new Auth\Plugin(
                $authBackend
            )
        );
        $request = new HTTP\Request('HEAD', '/file1', ['Authorization' => 'Basic '.base64_encode('user:pass')]);
        $response = $this->request($request);

        self::assertEquals(200, $response->getStatus());

        self::assertEquals(1, $count, 'Auth was triggered twice :(');
    }
}
