<?php

declare(strict_types=1);

namespace Sabre\DAV;

class ExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testStatus()
    {
        $e = new Exception();
        $this->assertEquals(500, $e->getHTTPCode());
    }

    public function testExceptionStatuses()
    {
        $c = [
            'Sabre\\DAV\\Exception\\NotAuthenticated' => 401,
            'Sabre\\DAV\\Exception\\InsufficientStorage' => 507,
        ];

        foreach ($c as $class => $status) {
            $obj = new $class();
            $this->assertEquals($status, $obj->getHTTPCode());
        }
    }
}
