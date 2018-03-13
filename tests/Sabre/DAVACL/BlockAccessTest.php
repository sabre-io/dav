<?php declare (strict_types=1);

namespace Sabre\DAVACL;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\DAV\Psr7RequestWrapper;

class BlockAccessTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DAV\Server
     */
    protected $server;
    protected $plugin;

    function setUp() {

        $nodes = [
            new DAV\SimpleCollection('testdir'),
        ];

        $this->server = new DAV\Server($nodes, null, null, function(){});
        $this->plugin = new Plugin();
        $this->plugin->setDefaultAcl([]);
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
        $this->server->addPlugin($this->plugin);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testGet() {

        $request = new ServerRequest('GET', '/testdir');
        $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);

    }

    function testGetDoesntExist() {

        $request = new ServerRequest('GET', '/foo');
        
        $r = $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);
        $this->assertTrue($r);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testHEAD() {
        $request = new ServerRequest('HEAD', '/testdir');
        $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testOPTIONS() {

        $request = new ServerRequest('OPTIONS', '/testdir');
        $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testPUT() {

        $request = new ServerRequest('PUT', '/testdir');
        $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testPROPPATCH() {
        $request = new ServerRequest('PROPPATCH', '/testdir');
        $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testCOPY() {
        $request = new ServerRequest('COPY', '/testdir');
        $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testMOVE() {
        $request = new ServerRequest('MOVE', '/testdir');
        $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testACL() {

        $request = new ServerRequest('ACL', '/testdir');
        $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testLOCK() {

        $request = new ServerRequest('LOCK', '/testdir');
        $this->server->emit('beforeMethod:GET', [new Psr7RequestWrapper($request), $this->server->httpResponse]);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testBeforeBind() {

        $this->server->emit('beforeBind', ['testdir/file']);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testBeforeUnbind() {

        $this->server->emit('beforeUnbind', ['testdir']);

    }

    function testPropFind() {

        $propFind = new DAV\PropFind('testdir', [
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
            '{DAV:}bar',
            '{DAV:}owner',
        ]);

        $r = $this->server->emit('propFind', [$propFind, new DAV\SimpleCollection('testdir')]);
        $this->assertTrue($r);

        $expected = [
            200 => [],
            404 => [],
            403 => [
                '{DAV:}displayname'      => null,
                '{DAV:}getcontentlength' => null,
                '{DAV:}bar'              => null,
                '{DAV:}owner'            => null,
            ],
        ];

        $this->assertEquals($expected, $propFind->getResultForMultiStatus());

    }

    function testBeforeGetPropertiesNoListing() {

        $this->plugin->hideNodesFromListings = true;
        $propFind = new DAV\PropFind('testdir', [
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
            '{DAV:}bar',
            '{DAV:}owner',
        ]);

        $r = $this->server->emit('propFind', [$propFind, new DAV\SimpleCollection('testdir')]);
        $this->assertFalse($r);

    }
}
