<?php

require_once 'Sabre/DAV/Auth/MockBackend.php';

class Sabre_DAVACL_PrincipalCollectionTest extends PHPUnit_Framework_TestCase {

    public function testBasic() {

        $backend = new Sabre_DAV_Auth_MockBackend();
        $pc = new Sabre_DAVACL_PrincipalCollection($backend);
        $this->assertTrue($pc instanceof Sabre_DAVACL_PrincipalCollection);

        $this->assertEquals('principals',$pc->getName());

    }

    /**
     * @depends testBasic
     */
    public function testGetChildren() {

        $backend = new Sabre_DAV_Auth_MockBackend();
        $pc = new Sabre_DAVACL_PrincipalCollection($backend);
        
        $children = $pc->getChildren();
        $this->assertTrue(is_array($children));

        foreach($children as $child) {
            $this->assertTrue($child instanceof Sabre_DAVACL_IPrincipal);
        }

    }

    /**
     * @depends testBasic
     * @expectedException Sabre_DAV_Exception_MethodNotAllowed
     */
    public function testGetChildrenRestricted() {

        $backend = new Sabre_DAV_Auth_MockBackend();
        $pc = new Sabre_DAV_Auth_PrincipalCollection($backend);
        $pc->disallowListing = true;
        
        $children = $pc->getChildren();

    }

    /**
     * @depends testBasic
     */
    public function testGetChildRestrictedSelf() {

        $backend = new Sabre_DAV_Auth_MockBackend();
        $backend->setCurrentUser('principals/admin');
        $pc = new Sabre_DAVACL_PrincipalCollection($backend);
        $pc->disallowListing = true;
        
        $child = $pc->getChild('admin');
        $this->assertTrue($child instanceof Sabre_DAVACL_IPrincipal);

    }


    /**
     * @depends testBasic
     * @expectedException Sabre_DAV_Exception_Forbidden
     */
    public function testGetChildRestrictedOtherUser() {

        $backend = new Sabre_DAV_Auth_MockBackend();
        $backend->setCurrentUser('principals/admin');
        $pc = new Sabre_DAVACL_PrincipalCollection($backend);
        $pc->disallowListing = true;
        
        $child = $pc->getChild('user1');

    }

    /**
     * @depends testBasic
     * @expectedException Sabre_DAV_Exception_Forbidden
     */
    public function testGetChildRestrictedNotLoggedIn() {

        $backend = new Sabre_DAV_Auth_MockBackend();
        $pc = new Sabre_DAVACL_PrincipalCollection($backend);
        $pc->disallowListing = true;
        
        $child = $pc->getChild('user1');

    }
}
