<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;

class BlockAccessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DAV\Server
     */
    protected $server;
    protected $plugin;

    public function setUp()
    {
        $nodes = [
            new DAV\SimpleCollection('testdir'),
        ];

        $this->server = new DAV\Server($nodes);
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
    public function testGet()
    {
        $this->server->httpRequest->setMethod('GET');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testGetDoesntExist()
    {
        $this->server->httpRequest->setMethod('GET');
        $this->server->httpRequest->setUrl('/foo');

        $r = $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
        $this->assertTrue($r);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testHEAD()
    {
        $this->server->httpRequest->setMethod('HEAD');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testOPTIONS()
    {
        $this->server->httpRequest->setMethod('OPTIONS');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testPUT()
    {
        $this->server->httpRequest->setMethod('PUT');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testPROPPATCH()
    {
        $this->server->httpRequest->setMethod('PROPPATCH');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testCOPY()
    {
        $this->server->httpRequest->setMethod('COPY');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testMOVE()
    {
        $this->server->httpRequest->setMethod('MOVE');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testACL()
    {
        $this->server->httpRequest->setMethod('ACL');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testLOCK()
    {
        $this->server->httpRequest->setMethod('LOCK');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testBeforeBind()
    {
        $this->server->emit('beforeBind', ['testdir/file']);
    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NeedPrivileges
     */
    public function testBeforeUnbind()
    {
        $this->server->emit('beforeUnbind', ['testdir']);
    }

    public function testPropFind()
    {
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
                '{DAV:}displayname' => null,
                '{DAV:}getcontentlength' => null,
                '{DAV:}bar' => null,
                '{DAV:}owner' => null,
            ],
        ];

        $this->assertEquals($expected, $propFind->getResultForMultiStatus());
    }

    public function testBeforeGetPropertiesNoListing()
    {
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
