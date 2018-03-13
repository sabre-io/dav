<?php declare (strict_types=1);

namespace Sabre\DAV\Locks;

use GuzzleHttp\Psr7\ServerRequest;
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

        $server = new DAV\Server($tree, null, null, function(){});
        $server->debugExceptions = true;
        $locksBackend = new Backend\File(SABRE_TEMPDIR . '/locksdb');
        $locksPlugin = new Plugin($locksBackend);
        $server->addPlugin($locksPlugin);

        $response = $server->handle($this->getLockRequest());
        $this->assertEquals(201, $response->getStatusCode(), 'Full response body:' . $response->getBody()->getContents());
        $this->assertNotEmpty($response->getHeaderLine('Lock-Token'));
        $lockToken = $response->getHeaderLine('Lock-Token');

        $response = $server->handle($this->getLockRequest2());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEmpty($response->getHeaderLine('Lock-Token'));

        $response = $server->handle($this->getPutRequest($lockToken));
        $this->assertEquals(204, $response->getStatusCode());

    }

    function getLockRequest() {

        $request = new ServerRequest('LOCK', '/Nouveau%20Microsoft%20Office%20Excel%20Worksheet.xlsx',
            [
           'Content-Type' => 'application/xml',
           'Timeout'      => 'Second-3600',
        ],'<D:lockinfo xmlns:D="DAV:">
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

        $request = new ServerRequest(
            'LOCK',
            '/~$Nouveau%20Microsoft%20Office%20Excel%20Worksheet.xlsx', [
           'Content-Type' => 'application/xml',
           'Timeout'      => 'Second-3600',
        ],'<D:lockinfo xmlns:D="DAV:">
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

        $request = new ServerRequest('PUT', '/Nouveau%20Microsoft%20Office%20Excel%20Worksheet.xlsx', [
           'If' => 'If: (' . $lockToken . ')',
        ],'FAKE BODY');
        return $request;

    }

}
