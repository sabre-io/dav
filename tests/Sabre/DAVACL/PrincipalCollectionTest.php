<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;


class PrincipalCollectionTest extends \PHPUnit_Framework_TestCase {

    public function testBasic() {

        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);
        $this->assertTrue($pc instanceof PrincipalCollection);

        $this->assertEquals('principals',$pc->getName());

    }

    /**
     * @depends testBasic
     */
    public function testGetChildren() {

        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);

        $children = $pc->getChildren();
        $this->assertTrue(is_array($children));

        foreach($children as $child) {
            $this->assertTrue($child instanceof IPrincipal);
        }

    }

    /**
     * @depends testBasic
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    public function testGetChildrenDisable() {

        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);
        $pc->disableListing = true;

        $children = $pc->getChildren();

    }

}
