<?php declare (strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAVACL;

class AddressBookRootTest extends \PHPUnit\Framework\TestCase {

    function testGetName() {

        $pBackend = new DAVACL\PrincipalBackend\Mock();
        $cBackend = new Backend\Mock();
        $root = new AddressBookRoot($pBackend, $cBackend);
        $this->assertEquals('addressbooks', $root->getName());

    }

    function testGetChildForPrincipal() {

        $pBackend = new DAVACL\PrincipalBackend\Mock();
        $cBackend = new Backend\Mock();
        $root = new AddressBookRoot($pBackend, $cBackend);

        $children = $root->getChildren();
        $this->assertEquals(3, count($children));

        $this->assertInstanceOf('Sabre\\CardDAV\\AddressBookHome', $children[0]);
        $this->assertEquals('user1', $children[0]->getName());

    }
}
