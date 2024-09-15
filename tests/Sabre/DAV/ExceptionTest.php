<?php

declare(strict_types=1);

namespace Sabre\DAV;

class ExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testStatus()
    {
        $e = new Exception();
        self::assertEquals(500, $e->getHTTPCode());
    }

    public function testExceptionStatuses()
    {
        $c = [
            \Sabre\DAV\Exception\NotAuthenticated::class => 401,
            \Sabre\DAV\Exception\InsufficientStorage::class => 507,
        ];

        foreach ($c as $class => $status) {
            $obj = new $class();
            self::assertEquals($status, $obj->getHTTPCode());
        }
    }
}
