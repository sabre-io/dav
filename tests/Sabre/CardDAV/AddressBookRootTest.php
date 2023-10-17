<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAVACL;

class AddressBookRootTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName()
    {
        $pBackend = new DAVACL\PrincipalBackend\Mock();
        $cBackend = new Backend\Mock();
        $root = new AddressBookRoot($pBackend, $cBackend);
        self::assertEquals('addressbooks', $root->getName());
    }

    public function testGetChildForPrincipal()
    {
        $pBackend = new DAVACL\PrincipalBackend\Mock();
        $cBackend = new Backend\Mock();
        $root = new AddressBookRoot($pBackend, $cBackend);

        $children = $root->getChildren();
        self::assertEquals(3, count($children));

        self::assertInstanceOf('Sabre\\CardDAV\\AddressBookHome', $children[0]);
        self::assertEquals('user1', $children[0]->getName());
    }
}
