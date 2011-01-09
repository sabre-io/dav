<?php

require_once 'Sabre/DAV/Auth/MockBackend.php';

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
        $this->assertInstanceOf('Sabre_DAV_Property_HrefList', $returnedProperties[200]['{DAV:}principal-collection-set']);

        $expected = array(
            'principals1/',
            'principals2/',
        );
        

        $this->assertEquals($expected, $returnedProperties[200]['{DAV:}principal-collection-set']->getHrefs());


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
        $this->assertInstanceOf('Sabre_DAV_Property_Principal', $returnedProperties[200]['{DAV:}current-user-principal']);
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
        $this->assertInstanceOf('Sabre_DAV_Property_Principal', $returnedProperties[200]['{DAV:}current-user-principal']);
        $this->assertEquals(Sabre_DAV_Property_Principal::HREF, $returnedProperties[200]['{DAV:}current-user-principal']->getType());
        $this->assertEquals('principals/admin/', $returnedProperties[200]['{DAV:}current-user-principal']->getHref());

    }

    function testSupportedPrivilegeSet() {

        $plugin = new Sabre_DAVACL_Plugin();

        $requestedProperties = array(
            '{DAV:}supported-privilege-set',
        );

        $returnedProperties = array(
            200 => array(),
            404 => array(),
        );


        $this->assertNull($plugin->beforeGetProperties('/', new Sabre_DAV_SimpleDirectory('root'), $requestedProperties, $returnedProperties));

        $this->assertEquals(1,count($returnedProperties[200]));
        $this->assertArrayHasKey('{DAV:}supported-privilege-set',$returnedProperties[200]);
        $this->assertInstanceOf('Sabre_DAVACL_Property_SupportedPrivilegeSet', $returnedProperties[200]['{DAV:}supported-privilege-set']);

        $server = new Sabre_DAV_Server();
        $prop = $returnedProperties[200]['{DAV:}supported-privilege-set'];

        $dom = new DOMDocument('1.0', 'utf-8');
        $root = $dom->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $dom->appendChild($root);
        $prop->serialize($server, $root);


        $xpaths = array(
            '/d:root' => 1,
            '/d:root/d:supported-privilege' => 1,
            '/d:root/d:supported-privilege/d:privilege' => 1,
            '/d:root/d:supported-privilege/d:privilege/d:all' => 1,
            '/d:root/d:supported-privilege/d:abstract' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege' => 2,
            '/d:root/d:supported-privilege/d:supported-privilege/d:privilege' => 2,
            '/d:root/d:supported-privilege/d:supported-privilege/d:privilege/d:read' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:privilege/d:write' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege' => 8,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege' => 8,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:read-acl' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:read-current-user-privilege-set' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:write-content' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:write-properties' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:write-acl' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:bind' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:unbind' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:unlock' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:abstract' => 8,
        );


        // reloading because php dom sucks 
        $dom2 = new DOMDocument('1.0', 'utf-8');
        $dom2->loadXML($dom->saveXML());

        $dxpath = new DOMXPath($dom2);
        $dxpath->registerNamespace('d','DAV:');
        foreach($xpaths as $xpath=>$count) {

            $this->assertEquals($count, $dxpath->query($xpath)->length, 'Looking for : ' . $xpath . ', we could only find ' . $dxpath->query($xpath)->length . ' elements, while we expected ' . $count);

        }

    }

}
