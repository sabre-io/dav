<?php

class Sabre_DAVACL_SimplePluginTest extends PHPUnit_Framework_TestCase {

    function testValues() {

        $aclPlugin = new Sabre_DAVACL_Plugin();
        $this->assertEquals('acl',$aclPlugin->getPluginName());
        $this->assertEquals(array('access-control'), $aclPlugin->getFeatures());

    }

}
