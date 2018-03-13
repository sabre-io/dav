<?php declare (strict_types=1);

namespace Sabre\DAVACL;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\DAV\Psr7RequestWrapper;

class AllowAccessTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DAV\Server
     */
    protected $server;

    function setUp() {

        $nodes = [
            new DAV\Mock\Collection('testdir', [
                'file1.txt' => 'contents',
            ]),
        ];

        $this->server = new DAV\Server($nodes);
        $this->server->addPlugin(
            new DAV\Auth\Plugin(
                new DAV\Auth\Backend\Mock()
            )
        );
        // Login
        $this->server->getPlugin('auth')->beforeMethod(
            new \Sabre\HTTP\Request('GET', '/'),
            new \Sabre\HTTP\Response()
        );
        $aclPlugin = new Plugin();
        $this->server->addPlugin($aclPlugin);

    }

    function testGet() {
        $request = new ServerRequest('GET', '/testdir');
        $this->assertTrue($this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]));
    }

    function testGetDoesntExist() {
        $request = new ServerRequest('GET', '/foo');
        $this->assertTrue($this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]));
    }

    function testHEAD() {
        $request = new ServerRequest('GET', '/testdir');
        $this->assertTrue($this->server->emit('beforeMethod:HEAD', [new Psr7RequestWrapper($request), $this->server->httpResponse]));

    }

    function testOPTIONS() {
        $request = new ServerRequest('OPTIONS', '/testdir');
        $this->assertTrue($this->server->emit('beforeMethod:OPTIONS', [new Psr7RequestWrapper($request), $this->server->httpResponse]));

    }

    function testPUT() {
        $request = new ServerRequest('PUT', '/testdir/file1.txt');
        $this->assertTrue($this->server->emit('beforeMethod:PUT', [new Psr7RequestWrapper($request), $this->server->httpResponse]));
    }

    function testPROPPATCH() {
        $request = new ServerRequest('PROPPATCH', '/testdir');
        $this->assertTrue($this->server->emit('beforeMethod:PROPPATCH', [new Psr7RequestWrapper($request), $this->server->httpResponse]));

    }

    function testCOPY() {
        $request = new ServerRequest('COPY', '/testdir');
        $this->assertTrue($this->server->emit('beforeMethod:COPY', [new Psr7RequestWrapper($request), $this->server->httpResponse]));
    }

    function testMOVE() {
        $request = new ServerRequest('MOVE', '/testdir');
        $this->assertTrue($this->server->emit('beforeMethod:MOVE', [new Psr7RequestWrapper($request), $this->server->httpResponse]));

    }

    function testLOCK() {
        $request = new ServerRequest('LOCK', '/testdir');
        $this->assertTrue($this->server->emit('beforeMethod:LOCK', [new Psr7RequestWrapper($request), $this->server->httpResponse]));

    }

    function testBeforeBind() {

        $this->assertTrue($this->server->emit('beforeBind', ['testdir/file']));

    }


    function testBeforeUnbind() {

        $this->assertTrue($this->server->emit('beforeUnbind', ['testdir']));

    }

}
