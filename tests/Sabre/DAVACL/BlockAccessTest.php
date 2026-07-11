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

    public function setup(): void
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

    public function testGet()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->httpRequest->setMethod('GET');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testGetDoesntExist()
    {
        $this->server->httpRequest->setMethod('GET');
        $this->server->httpRequest->setUrl('/foo');

        $r = $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
        self::assertTrue($r);
    }

    public function testHEAD()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->httpRequest->setMethod('HEAD');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testOPTIONS()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->httpRequest->setMethod('OPTIONS');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testPUT()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->httpRequest->setMethod('PUT');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testPROPPATCH()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->httpRequest->setMethod('PROPPATCH');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testCOPY()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->httpRequest->setMethod('COPY');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testMOVE()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->httpRequest->setMethod('MOVE');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testACL()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->httpRequest->setMethod('ACL');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testLOCK()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->httpRequest->setMethod('LOCK');
        $this->server->httpRequest->setUrl('/testdir');

        $this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]);
    }

    public function testBeforeBind()
    {
        $this->expectException(Exception\NeedPrivileges::class);
        $this->server->emit('beforeBind', ['testdir/file']);
    }

    public function testBeforeUnbind()
    {
        $this->expectException(Exception\NeedPrivileges::class);
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
        self::assertTrue($r);

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

        self::assertEquals($expected, $propFind->getResultForMultiStatus());
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
        self::assertFalse($r);
    }
}
