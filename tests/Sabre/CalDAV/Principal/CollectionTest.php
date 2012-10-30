<?php

namespace Sabre\CalDAV\Principal;
use Sabre\DAVACL;

require_once 'Sabre/DAVACL/MockPrincipalBackend.php';

class CollectionTest extends \PHPUnit_Framework_TestCase {

    function testGetChildForPrincipal() {

        $back = new DAVACL\MockPrincipalBackend();
        $col = new Collection($back);
        $r = $col->getChildForPrincipal(array(
            'uri' => 'principals/admin',
        ));
        $this->assertInstanceOf('Sabre\\CalDAV\\Principal\\User', $r);

    }

}
