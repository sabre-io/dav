<?php declare (strict_types=1);

namespace Sabre\DAV\Locks;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/TestUtil.php';

class MSWordTest extends \PHPUnit_Framework_TestCase {

    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    function testLockEtc() {

        mkdir(SABRE_TEMPDIR . '/mstest');
        $tree = new DAV\FS\Directory(SABRE_TEMPDIR . '/mstest');

        $server = new DAV\Server($tree);
        $server->debugExceptions = true;
        $locksBackend = new Backend\File(SABRE_TEMPDIR . '/locksdb');
        $locksPlugin = new Plugin($locksBackend);
        $server->addPlugin($locksPlugin);

        $server->httpRequest = $this->getLockRequest();
        $server->sapi = new HTTP\SapiMock();
        $server->start();

        $response = $server->httpResponse->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), 'Full response body:' . $response->getBody()->getContents());
        $this->assertNotEmpty($response->getHeaderLine('Lock-Token'));
        $lockToken = $response->getHeaderLine('Lock-Token');

        //sleep(10);
        $server->httpRequest = $this->getLockRequest2();
        $server->start();

        $response = $server->httpResponse->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($response->getHeaderLine('Lock-Token'));

        //sleep(10);
        $server->httpRequest = $this->getPutRequest($lockToken);
        $server->start();

        $response = $server->httpResponse->getResponse();
        $this->assertEquals(204, $response->getStatusCode());

    }

    function getLockRequest() {

        $request = HTTP\Sapi::createFromServerArray([
           'REQUEST_METHOD'    => 'LOCK',
           'HTTP_CONTENT_TYPE' => 'application/xml',
           'HTTP_TIMEOUT'      => 'Second-3600',
           'REQUEST_URI'       => '/Nouveau%20Microsoft%20Office%20Excel%20Worksheet.xlsx',
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
    function getLockRequest2() {

        $request = HTTP\Sapi::createFromServerArray([
           'REQUEST_METHOD'    => 'LOCK',
           'HTTP_CONTENT_TYPE' => 'application/xml',
           'HTTP_TIMEOUT'      => 'Second-3600',
           'REQUEST_URI'       => '/~$Nouveau%20Microsoft%20Office%20Excel%20Worksheet.xlsx',
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

    function getPutRequest($lockToken) {

        $request = HTTP\Sapi::createFromServerArray([
           'REQUEST_METHOD' => 'PUT',
           'REQUEST_URI'    => '/Nouveau%20Microsoft%20Office%20Excel%20Worksheet.xlsx',
           'HTTP_IF'        => 'If: (' . $lockToken . ')',
        ]);
        $request->setBody('FAKE BODY');
        return $request;

    }

}
