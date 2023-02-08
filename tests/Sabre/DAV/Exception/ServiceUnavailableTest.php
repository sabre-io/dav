<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

class ServiceUnavailableTest extends \PHPUnit\Framework\TestCase
{
    public function testGetHTTPCode()
    {
        $ex = new ServiceUnavailable();
        self::assertEquals(503, $ex->getHTTPCode());
    }
}
