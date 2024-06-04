<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks;

use Sabre\DAV;
use Sabre\HTTP;

class MSWordTest extends \PHPUnit\Framework\TestCase
{
    public function teardown(): void
    {
        \Sabre\TestUtil::clearTempDir();
    }

    public function testLockEtc()
    {
        mkdir(\Sabre\TestUtil::SABRE_TEMPDIR.'/mstest');
        $tree = new DAV\FS\Directory(\Sabre\TestUtil::SABRE_TEMPDIR.'/mstest');

        $server = new DAV\Server($tree);
        $server->debugExceptions = true;
        $locksBackend = new Backend\File(\Sabre\TestUtil::SABRE_TEMPDIR.'/locksdb');
        $locksPlugin = new Plugin($locksBackend);
        $server->addPlugin($locksPlugin);

        $response1 = new HTTP\ResponseMock();

        $server->httpRequest = $this->getLockRequest();
        $server->httpResponse = $response1;
        $server->sapi = new HTTP\SapiMock();
        $server->exec();

        self::assertEquals(201, $server->httpResponse->getStatus(), 'Full response body:'.$response1->getBodyAsString());
        self::assertTrue((bool) $server->httpResponse->getHeaders('Lock-Token'));
        $lockToken = $server->httpResponse->getHeader('Lock-Token');

        // sleep(10);

        $response2 = new HTTP\ResponseMock();

        $server->httpRequest = $this->getLockRequest2();
        $server->httpResponse = $response2;
        $server->exec();

        self::assertEquals(201, $server->httpResponse->status);
        self::assertTrue((bool) $server->httpResponse->getHeaders('Lock-Token'));

        // sleep(10);

        $response3 = new HTTP\ResponseMock();
        $server->httpRequest = $this->getPutRequest($lockToken);
        $server->httpResponse = $response3;
        $server->exec();

        self::assertEquals(204, $server->httpResponse->status);
    }

    public function getLockRequest()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'LOCK',
            'HTTP_CONTENT_TYPE' => 'application/xml',
            'HTTP_TIMEOUT' => 'Second-3600',
            'REQUEST_URI' => '/Nouveau%20Microsoft%20Office%20Excel%20Worksheet.xlsx',
        ]);

        $request->setBody('<D:lockinfo xmlns:D="DAV:">
    <D:lockscope>
        <D:exclusive />
    </D:lockscope>
    <D:locktype>
        <D:write />
    </D:locktype>
    <D:owner>
        <D:href>PC-Vista\User</D:href>
    </D:owner>
</D:lockinfo>');

        return $request;
    }

    public function getLockRequest2()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'LOCK',
            'HTTP_CONTENT_TYPE' => 'application/xml',
            'HTTP_TIMEOUT' => 'Second-3600',
            'REQUEST_URI' => '/~$Nouveau%20Microsoft%20Office%20Excel%20Worksheet.xlsx',
        ]);

        $request->setBody('<D:lockinfo xmlns:D="DAV:">
    <D:lockscope>
        <D:exclusive />
    </D:lockscope>
    <D:locktype>
        <D:write />
    </D:locktype>
    <D:owner>
        <D:href>PC-Vista\User</D:href>
    </D:owner>
</D:lockinfo>');

        return $request;
    }

    public function getPutRequest($lockToken)
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/Nouveau%20Microsoft%20Office%20Excel%20Worksheet.xlsx',
            'HTTP_IF' => 'If: ('.$lockToken.')',
        ]);
        $request->setBody('FAKE BODY');

        return $request;
    }
}
