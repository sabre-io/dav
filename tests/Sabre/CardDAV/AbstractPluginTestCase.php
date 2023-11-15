<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use PHPUnit\Framework\TestCase;
use Sabre\CardDAV\Backend\Mock;
use Sabre\DAV\Server;
use Sabre\DAVACL;
use Sabre\HTTP;

abstract class AbstractPluginTestCase extends TestCase
{
    /**
     * @var Plugin
     */
    protected $plugin;
    /**
     * @var Server
     */
    protected $server;
    /**
     * @var Mock;
     */
    protected $backend;

    public function setup(): void
    {
        $this->backend = new Backend\Mock();
        $principalBackend = new DAVACL\PrincipalBackend\Mock();

        $tree = [
            new AddressBookRoot($principalBackend, $this->backend),
            new DAVACL\PrincipalCollection($principalBackend),
        ];

        $this->plugin = new Plugin();
        $this->plugin->directories = ['directory'];
        $this->server = new Server($tree);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->addPlugin($this->plugin);
        $this->server->debugExceptions = true;
    }
}
