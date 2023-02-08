<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;

class AllowAccessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DAV\Server
     */
    protected $server;

    public function setup(): void
    {
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

    public function testGet()
    {
        $this->server->httpRequest->setMethod('GET');
        $this->server->httpRequest->setUrl('/testdir');

        self::assertTrue($this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]));
    }

    public function testGetDoesntExist()
    {
        $this->server->httpRequest->setMethod('GET');
        $this->server->httpRequest->setUrl('/foo');

        self::assertTrue($this->server->emit('beforeMethod:GET', [$this->server->httpRequest, $this->server->httpResponse]));
    }

    public function testHEAD()
    {
        $this->server->httpRequest->setMethod('HEAD');
        $this->server->httpRequest->setUrl('/testdir');

        self::assertTrue($this->server->emit('beforeMethod:HEAD', [$this->server->httpRequest, $this->server->httpResponse]));
    }

    public function testOPTIONS()
    {
        $this->server->httpRequest->setMethod('OPTIONS');
        $this->server->httpRequest->setUrl('/testdir');

        self::assertTrue($this->server->emit('beforeMethod:OPTIONS', [$this->server->httpRequest, $this->server->httpResponse]));
    }

    public function testPUT()
    {
        $this->server->httpRequest->setMethod('PUT');
        $this->server->httpRequest->setUrl('/testdir/file1.txt');

        self::assertTrue($this->server->emit('beforeMethod:PUT', [$this->server->httpRequest, $this->server->httpResponse]));
    }

    public function testPROPPATCH()
    {
        $this->server->httpRequest->setMethod('PROPPATCH');
        $this->server->httpRequest->setUrl('/testdir');

        self::assertTrue($this->server->emit('beforeMethod:PROPPATCH', [$this->server->httpRequest, $this->server->httpResponse]));
    }

    public function testCOPY()
    {
        $this->server->httpRequest->setMethod('COPY');
        $this->server->httpRequest->setUrl('/testdir');

        self::assertTrue($this->server->emit('beforeMethod:COPY', [$this->server->httpRequest, $this->server->httpResponse]));
    }

    public function testMOVE()
    {
        $this->server->httpRequest->setMethod('MOVE');
        $this->server->httpRequest->setUrl('/testdir');

        self::assertTrue($this->server->emit('beforeMethod:MOVE', [$this->server->httpRequest, $this->server->httpResponse]));
    }

    public function testLOCK()
    {
        $this->server->httpRequest->setMethod('LOCK');
        $this->server->httpRequest->setUrl('/testdir');

        self::assertTrue($this->server->emit('beforeMethod:LOCK', [$this->server->httpRequest, $this->server->httpResponse]));
    }

    public function testBeforeBind()
    {
        self::assertTrue($this->server->emit('beforeBind', ['testdir/file']));
    }

    public function testBeforeUnbind()
    {
        self::assertTrue($this->server->emit('beforeUnbind', ['testdir']));
    }
}
