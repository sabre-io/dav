<?php

class Sabre_DAVACL_PluginPropertiesTest extends PHPUnit_Framework_TestCase {

    function testPrincipalCollectionSet() {

        $plugin = new Sabre_DAVACL_Plugin();
        $plugin->principalCollectionSet = array(
            'principals1',
            'principals2',
        );

        $requestedProperties = array(
            '{DAV:}principal-collection-set',
        );

        $returnedProperties = array(
            200 => array(),
            404 => array(),
        );


        $this->assertNull($plugin->beforeGetProperties('/', new Sabre_DAV_SimpleDirectory('root'), $requestedProperties, $returnedProperties));

        $this->assertEquals(1,count($returnedProperties[200]));
        $this->assertArrayHasKey('{DAV:}principal-collection-set',$returnedProperties[200]);
        $this->assertType('Sabre_DAV_Property_HrefList', $returnedProperties[200]['{DAV:}principal-collection-set']);

        $this->assertEquals($plugin->principalCollectionSet, $returnedProperties[200]['{DAV:}principal-collection-set']->getHrefs());


    }

    function testCurrentUserPrincipal() {

        $fakeServer = new Sabre_DAV_Server();
        $plugin = new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'realm');
        $fakeServer->addPlugin($plugin);
        $plugin = new Sabre_DAVACL_Plugin();
        $fakeServer->addPlugin($plugin);


        
        $requestedProperties = array(
            '{DAV:}current-user-principal',
        );

        $returnedProperties = array(
            200 => array(),
            404 => array(),
        );

        $this->assertNull($plugin->beforeGetProperties('/', new Sabre_DAV_SimpleDirectory('root'), $requestedProperties, $returnedProperties));

        $this->assertEquals(1,count($returnedProperties[200]));
        $this->assertArrayHasKey('{DAV:}current-user-principal',$returnedProperties[200]);
        $this->assertType('Sabre_DAV_Property_Principal', $returnedProperties[200]['{DAV:}current-user-principal']);
        $this->assertEquals(Sabre_DAV_Property_Principal::UNAUTHENTICATED, $returnedProperties[200]['{DAV:}current-user-principal']->getType());

        // This will force the login
        $fakeServer->broadCastEvent('beforeMethod',array('GET','/'));


        $requestedProperties = array(
            '{DAV:}current-user-principal',
        );

        $returnedProperties = array(
            200 => array(),
            404 => array(),
        );


        $this->assertNull($plugin->beforeGetProperties('/', new Sabre_DAV_SimpleDirectory('root'), $requestedProperties, $returnedProperties));


        $this->assertEquals(1,count($returnedProperties[200]));
        $this->assertArrayHasKey('{DAV:}current-user-principal',$returnedProperties[200]);
        $this->assertType('Sabre_DAV_Property_Principal', $returnedProperties[200]['{DAV:}current-user-principal']);
        $this->assertEquals(Sabre_DAV_Property_Principal::HREF, $returnedProperties[200]['{DAV:}current-user-principal']->getType());
        $this->assertEquals('principals/admin', $returnedProperties[200]['{DAV:}current-user-principal']->getHref());
    }

}
