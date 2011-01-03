<?php

require_once 'Sabre/DAV/Auth/MockBackend.php';
require_once 'Sabre/DAVACL/MockPrincipal.php';
require_once 'Sabre/DAVACL/MockACLNode.php';

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
                    '{DAV:}bind',
                    '{DAV:}unbind',
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
            '{DAV:}bind' => array(
                'abstract' => true,
                'aggregates' => array(),
                'concrete' => '{DAV:}write',
            ),
            '{DAV:}unbind' => array(
                'abstract' => true,
                'aggregates' => array(),
                'concrete' => '{DAV:}write',
            ),

        );
        
        $plugin = new Sabre_DAVACL_Plugin();
        $this->assertEquals($expected, $plugin->getFlatPrivilegeSet());


    }

    function testCurrentUserPrincipalsNotLoggedIn() {

        $acl = new Sabre_DAVACL_Plugin();
        $server = new Sabre_DAV_Server(); 
        $server->addPlugin($acl);     
           
        $this->assertEquals(array(),$acl->getCurrentUserPrincipals());

    }

    function testCurrentUserPrincipalsSimple() {

        $tree = array(

            new Sabre_DAV_SimpleDirectory('principals', array(
                new Sabre_DAVACL_MockPrincipal('admin','principals/admin'),
            ))

        );

        $acl = new Sabre_DAVACL_Plugin();
        $server = new Sabre_DAV_Server($tree); 
        $server->addPlugin($acl);

        $auth = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'SabreDAV');
        $server->addPlugin($auth);

        //forcing login
        $auth->beforeMethod('GET','/'); 
           
        $this->assertEquals(array('principals/admin'),$acl->getCurrentUserPrincipals());

    }

    function testCurrentUserPrincipalsGroups() {

        $tree = array(

            new Sabre_DAV_SimpleDirectory('principals', array(
                new Sabre_DAVACL_MockPrincipal('admin','principals/admin',array('principals/administrators', 'principals/everyone')),
                new Sabre_DAVACL_MockPrincipal('administrators','principals/administrators',array('principals/groups'), array('principals/admin')),
                new Sabre_DAVACL_MockPrincipal('everyone','principals/everyone',array(), array('principals/admin')),
                new Sabre_DAVACL_MockPrincipal('groups','principals/groups',array(), array('principals/administrators')),
            ))

        );

        $acl = new Sabre_DAVACL_Plugin();
        $server = new Sabre_DAV_Server($tree); 
        $server->addPlugin($acl);

        $auth = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'SabreDAV');
        $server->addPlugin($auth);

        //forcing login
        $auth->beforeMethod('GET','/'); 

        $expected = array(
            'principals/admin',
            'principals/administrators',
            'principals/everyone',
            'principals/groups',
        );

        $this->assertEquals($expected,$acl->getCurrentUserPrincipals());

    }

    function testGetACL() {

        $acl = array(
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}read',
            ),
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}write',
            ),
        );


        $tree = array(
            new Sabre_DAVACL_MockACLNode('foo',$acl),
        );

        $server = new Sabre_DAV_Server($tree);
        $aclPlugin = new Sabre_DAVACL_Plugin();
        $server->addPlugin($aclPlugin);

        $this->assertEquals($acl,$aclPlugin->getACL('foo'));

    }

    function testGetCurrentUserPrivilegeSet() {

        $acl = array(
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}read',
            ),
            array(
                'principal' => 'principals/user1',
                'privilege' => '{DAV:}read',
            ),
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}write',
            ),
        );


        $tree = array(
            new Sabre_DAVACL_MockACLNode('foo',$acl),

            new Sabre_DAV_SimpleDirectory('principals', array(
                new Sabre_DAVACL_MockPrincipal('admin','principals/admin'),
            )),

        );

        $server = new Sabre_DAV_Server($tree);
        $aclPlugin = new Sabre_DAVACL_Plugin();
        $server->addPlugin($aclPlugin);

        $auth = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'SabreDAV');
        $server->addPlugin($auth);

        //forcing login
        $auth->beforeMethod('GET','/');

        $expected = array(
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}read',
            ),
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}write',
            ),
        );

        $this->assertEquals($expected,$aclPlugin->getCurrentUserPrivilegeSet('foo'));

    }

}




