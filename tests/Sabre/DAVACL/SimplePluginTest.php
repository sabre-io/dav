<?php

class Sabre_DAVACL_SimplePluginTest extends PHPUnit_Framework_TestCase {

    function testValues() {

        $aclPlugin = new Sabre_DAVACL_Plugin();
        $this->assertEquals('acl',$aclPlugin->getPluginName());
        $this->assertEquals(array('access-control'), $aclPlugin->getFeatures());

        $this->assertEquals(
            array(
                '{DAV:}expand-properties',
                '{DAV:}principal-property-search',
                '{DAV:}principal-search-property-set'
            ), 
            $aclPlugin->getSupportedReportSet(''));

        $this->assertEquals(array(), $aclPlugin->getMethods(''));

    }

    function testGetFlatPrivilegeSet() {

        $expected = array(
            '{DAV:}all' => array(
                'abstract' => true,
                'aggregates' => array(
                    '{DAV:}read',
                    '{DAV:}write',
                ),
                'concrete' => null,
            ),
            '{DAV:}read' => array(
                'abstract' => false,
                'aggregates' => array(
                    '{DAV:}read-acl',
                    '{DAV:}read-current-user-privilege-set',
                ),
                'concrete' => '{DAV:}read',
            ),
            '{DAV:}read-acl' => array(
                'abstract' => true,
                'aggregates' => array(),
                'concrete' => '{DAV:}read',
            ),
            '{DAV:}read-current-user-privilege-set' => array(
                'abstract' => true,
                'aggregates' => array(),
                'concrete' => '{DAV:}read',
            ),
            '{DAV:}write' => array(
                'abstract' => false,
                'aggregates' => array(
                    '{DAV:}write-acl',
                    '{DAV:}write-properties',
                    '{DAV:}write-content',
                    '{DAV:}unlock',
                ),
                'concrete' => '{DAV:}write',
            ),
            '{DAV:}write-acl' => array(
                'abstract' => true,
                'aggregates' => array(),
                'concrete' => '{DAV:}write',
            ),
            '{DAV:}write-properties' => array(
                'abstract' => true,
                'aggregates' => array(),
                'concrete' => '{DAV:}write',
            ),
            '{DAV:}write-content' => array(
                'abstract' => true,
                'aggregates' => array(),
                'concrete' => '{DAV:}write',
            ),
            '{DAV:}unlock' => array(
                'abstract' => true,
                'aggregates' => array(),
                'concrete' => '{DAV:}write',
            ),
        );
        
        $plugin = new Sabre_DAVACL_Plugin();
        $this->assertEquals($expected, $plugin->getFlatPrivilegeSet());


    }

}
