<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Principal;

use Sabre\DAVACL;

class ProxyWriteTest extends ProxyReadTest
{
    public function getInstance()
    {
        $backend = new DAVACL\PrincipalBackend\Mock();
        $principal = new ProxyWrite($backend, [
            'uri' => 'principal/user',
        ]);
        $this->backend = $backend;

        return $principal;
    }

    public function testGetName()
    {
        $i = $this->getInstance();
        $this->assertEquals('calendar-proxy-write', $i->getName());
    }

    public function testGetDisplayName()
    {
        $i = $this->getInstance();
        $this->assertEquals('calendar-proxy-write', $i->getDisplayName());
    }

    public function testGetPrincipalUri()
    {
        $i = $this->getInstance();
        $this->assertEquals('principal/user/calendar-proxy-write', $i->getPrincipalUrl());
    }
}
