<?php

declare(strict_types=1);

namespace Sabre\DAV;

class CorePluginTest extends \PHPUnit\Framework\TestCase
{
    public function testGetInfo()
    {
        $corePlugin = new CorePlugin();
        self::assertEquals('core', $corePlugin->getPluginInfo()['name']);
    }
}
