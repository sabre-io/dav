<?php

declare(strict_types=1);

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV\FSExt\File;
use Sabre\DAV\Server;
use Sabre\HTTP;

/**
 * This test is an end-to-end sabredav test that goes through all
 * the cases in the specification.
 *
 * See: http://sabre.io/dav/http-patch/
 */
class SpecificationTest extends \PHPUnit\Framework\TestCase
{
    protected $server;

    public function setup(): void
    {
        $tree = [
            new File(\Sabre\TestUtil::SABRE_TEMPDIR.'/foobar.txt'),
        ];
        $server = new Server($tree);
        $server->debugExceptions = true;
        $server->addPlugin(new Plugin());

        $tree[0]->put('1234567890');

        $this->server = $server;
    }

    public function teardown(): void
    {
        \Sabre\TestUtil::clearTempDir();
    }

    /**
     * @param string $headerValue
     * @param string $httpStatus
     * @param string $endResult
     * @param int    $contentLength
     *
     * @dataProvider data
     */
    public function testUpdateRange($headerValue, $httpStatus, $endResult, $contentLength = 4)
    {
        $headers = [
            'Content-Type' => 'application/x-sabredav-partialupdate',
            'X-Update-Range' => $headerValue,
        ];

        if ($contentLength) {
            $headers['Content-Length'] = (string) $contentLength;
        }

        $request = new HTTP\Request('PATCH', '/foobar.txt', $headers, '----');

        $request->setBody('----');
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->exec();

        self::assertEquals($httpStatus, $this->server->httpResponse->status, 'Incorrect http status received: '.$this->server->httpResponse->body);
        if (!is_null($endResult)) {
            self::assertEquals($endResult, file_get_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/foobar.txt'));
        }
    }

    public function data()
    {
        return [
            // Problems
            ['foo',       400, null],
            ['bytes=0-3', 411, null, 0],
            ['bytes=4-1', 416, null],

            ['bytes=0-3', 204, '----567890'],
            ['bytes=1-4', 204, '1----67890'],
            ['bytes=0-',  204, '----567890'],
            ['bytes=-4',  204, '123456----'],
            ['bytes=-2',  204, '12345678----'],
            ['bytes=2-',  204, '12----7890'],
            ['append',    204, '1234567890----'],
        ];
    }
}
