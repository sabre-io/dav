<?php

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
class SpecificationTest extends \PHPUnit_Framework_TestCase {

    protected $server;

    public function setUp() {

        $tree = array(
            new File(SABRE_TEMPDIR . '/foobar.txt')
        );
        $server = new Server($tree);
        $server->debugExceptions = true;
        $server->addPlugin(new Plugin());

        $tree[0]->put('1234567890');

        $this->server = $server;

    }

    public function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    /**
     * @dataProvider data
     */
    public function testUpdateRange($headerValue, $httpStatus, $endResult, $contentLength = 4) {

        $vars = array(
            'REQUEST_METHOD' => 'PATCH',
            'HTTP_CONTENT_TYPE' => 'application/x-sabredav-partialupdate',
            'HTTP_X_UPDATE_RANGE' => $headerValue,
            'REQUEST_URI' => '/foobar.txt',
        );
        if ($contentLength) {
            $vars['HTTP_CONTENT_LENGTH'] = (string)$contentLength;
        }

        $request = new HTTP\Request($vars);

        $request->setBody('----');
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->exec();

        $this->assertEquals($httpStatus, $this->server->httpResponse->status, 'Incorrect http status received: ' . $this->server->httpResponse->body);
        if (!is_null($endResult)) {
            $this->assertEquals($endResult, file_get_contents(SABRE_TEMPDIR . '/foobar.txt'));
        }

    } 

    public function data() {

        return array(
            // Problems
            array('foo',       'HTTP/1.1 400 Bad request', null),
            array('bytes=0-3', 'HTTP/1.1 411 Length Required', null, 0),
            array('bytes=4-1', 'HTTP/1.1 416 Requested Range Not Satisfiable', null),

            array('bytes=0-3', 'HTTP/1.1 204 No Content', '----567890'),
            array('bytes=1-4', 'HTTP/1.1 204 No Content', '1----67890'),
            array('bytes=0-',  'HTTP/1.1 204 No Content', '----567890'),
            array('bytes=-4',  'HTTP/1.1 204 No Content', '123456----'),
            array('bytes=-2',  'HTTP/1.1 204 No Content', '12345678----'),
            array('bytes=2-',  'HTTP/1.1 204 No Content', '12----7890'),
            array('append',    'HTTP/1.1 204 No Content', '1234567890----'),

        );

    }

}
