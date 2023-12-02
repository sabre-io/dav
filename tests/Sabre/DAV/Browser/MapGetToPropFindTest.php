<?php

declare(strict_types=1);

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\HTTP;

class MapGetToPropFindTest extends DAV\AbstractServerTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->server->addPlugin(new MapGetToPropFind());
    }

    public function testCollectionGet()
    {
        $serverVars = [
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(207, $this->response->status, 'Incorrect status response received. Full response body: '.$this->response->getBodyAsString());
        self::assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'DAV' => ['1, 3, extended-mkcol'],
            'Vary' => ['Brief,Prefer'],
            ],
            $this->response->getHeaders()
        );
    }
}
