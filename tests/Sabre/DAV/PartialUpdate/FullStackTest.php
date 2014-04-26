<?php

/**
 * This test sets up the entire stack and tests every bit.
 */
class Sabre_DAV_PartialUpdate_FullStackTest extends PHPUnit_Framework_TestCase {

    protected $server;

    public function setUp() {

        $tree = array(
            new Sabre_DAV_FSExt_File(SABRE_TEMPDIR . '/foobar.txt')
        );
        $server = new Sabre_DAV_Server($tree);
        $server->addPlugin(new Sabre_DAV_PartialUpdate_Plugin());

        $tree[0]->put('1234567890');

        $this->server = $server;

    }

    public function tearDown() {

        Sabre_TestUtil::clearTempDir();

    }

    public function testUpdateRange() {

        $request = new Sabre_HTTP_Request(array(
            'REQUEST_METHOD' => 'PATCH',
            'HTTP_CONTENT_TYPE' => 'application/x-sabredav-partialupdate',
            'HTTP_X_UPDATE_RANGE' => 'bytes=3-4',
            'REQUEST_URI' => '/foobar.txt',
            'HTTP_CONTENT_LENGTH' => '2',
        ));
        $request->setBody('--');
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new Sabre_HTTP_ResponseMock();
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 204 No Content', $this->server->httpResponse->status, 'Incorrect http status received: ' . $this->server->httpResponse->body);
        $this->assertEquals('12--4567890', file_get_contents(SABRE_TEMPDIR . '/foobar.txt'));

    } 

}
