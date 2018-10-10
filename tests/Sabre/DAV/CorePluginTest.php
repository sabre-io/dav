<?php

declare(strict_types=1);

namespace Sabre\DAV;

class CorePluginTest extends \PHPUnit\Framework\TestCase
{
    public function testGetInfo()
    {
        $corePlugin = new CorePlugin();
        $this->assertEquals('core', $corePlugin->getPluginInfo()['name']);
    }
}
