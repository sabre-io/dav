<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class TestPlugin extends ServerPlugin
{
    public $beforeMethod;

    public function getFeatures()
    {
        return ['drinking'];
    }

    public function getHTTPMethods($uri)
    {
        return ['BEER', 'WINE'];
    }

    public function initialize(Server $server)
    {
        $server->on('beforeMethod:*', [$this, 'beforeMethod']);
    }

    public function beforeMethod(RequestInterface $request, ResponseInterface $response)
    {
        $this->beforeMethod = $request->getMethod();

        return true;
    }
}
