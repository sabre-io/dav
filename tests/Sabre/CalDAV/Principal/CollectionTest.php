<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Principal;

use Sabre\DAVACL;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testGetChildForPrincipal()
    {
        $back = new DAVACL\PrincipalBackend\Mock();
        $col = new Collection($back);
        $r = $col->getChildForPrincipal([
            'uri' => 'principals/admin',
        ]);
        self::assertInstanceOf('Sabre\\CalDAV\\Principal\\User', $r);
    }
}
