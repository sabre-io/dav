<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAVServerTest;
use Sabre\HTTP;

/**
 * Tests related to the HEAD request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HttpHeadTest extends DAVServerTest {

    /**
     * Sets up the DAV tree.
     *
     * @return void
     */
    function setUpTree() {

        $this->tree = new Mock\Collection('root', [
            'file1' => 'foo',
            new Mock\Collection('dir', []),
            new Mock\StreamingFile('streaming', 'stream')
        ]);

    }

    function testHEAD() {

        $request = new ServerRequest('HEAD', '/file1');
        $response = $this->request($request, 200);

        $headers = $response->getHeaders();
        // Removing Last-Modified because it keeps changing.
        unset($headers['Last-Modified']);

        $this->assertEquals(
            [

                'Content-Type'    => ['application/octet-stream'],
                'Content-Length'  => [3],
                'ETag'            => ['"' . md5('foo') . '"'],
            ], $headers,
            print_r($headers, true)
        );

        $this->assertEmpty($response->getBody()->getContents());

    }

    /**
     * According to the specs, HEAD should behave identical to GET. But, broken
     * clients needs HEAD requests on collections to respond with a 200, so
     * that's what we do.
     */
    function testHEADCollection() {

        $request = new ServerRequest('HEAD', '/dir');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatusCode());

    }

    /**
     * HEAD automatically internally maps to GET via a sub-request.
     * The Auth plugin must not be triggered twice for these, so we'll
     * test for that.
     */
    function testDoubleAuth() {

        $count = 0;

        $authBackend = new Auth\Backend\BasicCallBack(function($userName, $password) use (&$count) {
            $count++;
            return true;
        });
        $this->server->addPlugin(
            new Auth\Plugin(
                $authBackend
            )
        );
        $request = new ServerRequest('HEAD', '/file1', ['Authorization' => 'Basic ' . base64_encode('user:pass')]);
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(1, $count, 'Auth was triggered twice :(');

    }

}
