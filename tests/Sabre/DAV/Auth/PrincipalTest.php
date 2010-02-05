<?php

class Sabre_DAV_Auth_PrincipalTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {

        $principal = new Sabre_DAV_Auth_Principal('principals/admin');
        $this->assertTrue($principal instanceof Sabre_DAV_Auth_Principal);

    }

    public function testGetName() {

        $principal = new Sabre_DAV_Auth_Principal('principals/admin');
        $this->assertEquals('admin',$principal->getName());

    }

    public function testGetDisplayName() {

        $principal = new Sabre_DAV_Auth_Principal('principals/admin');
        $this->assertEquals('admin',$principal->getDisplayname());

        $principal = new Sabre_DAV_Auth_Principal('principals/admin',array(
            '{DAV:}displayname' => 'Mr. Admin'
        ));
        $this->assertEquals('Mr. Admin',$principal->getDisplayname());

    }

    public function testGetPropertiesAll() {

        $principal = new Sabre_DAV_Auth_Principal('principals/admin',array(
            '{DAV:}displayname' => 'Mr. Admin',
            '{http://www.example.org/custom}' => 'Custom',
        ));

        $props = $principal->getProperties(array());
        $keys = array(
            '{DAV:}resourcetype',
            '{DAV:}displayname',
        );

        $this->assertEquals($keys,array_keys($props));
        $this->assertEquals('Mr. Admin',$props['{DAV:}displayname']);
        $this->assertEquals('{DAV:}principal',$props['{DAV:}resourcetype']->getValue());

    }

    public function testGetProperties() {

        $principal = new Sabre_DAV_Auth_Principal('principals/admin',array(
            '{DAV:}displayname' => 'Mr. Admin',
            '{http://www.example.org/custom}' => 'Custom',
        ));

        $keys = array(
            '{DAV:}resourcetype',
            '{DAV:}displayname',
            '{http://www.example.org/custom}',
            '{DAV:}alternate-URI-set',
            '{DAV:}principal-URL',
            '{DAV:}group-member-set',
            '{DAV:}group-membership',
        );
        $props = $principal->getProperties($keys);

        foreach($keys as $key) $this->assertArrayHasKey($key,$props);

        $this->assertEquals('Mr. Admin',$props['{DAV:}displayname']);
        $this->assertEquals('{DAV:}principal',$props['{DAV:}resourcetype']->getValue());

        $this->assertNull($props['{DAV:}alternate-URI-set']);
        $this->assertEquals('principals/admin',$props['{DAV:}principal-URL']->getHref());
        $this->assertNull($props['{DAV:}group-member-set']);
        $this->assertNull($props['{DAV:}group-membership']);

    }

    /**
     * @expectedException Sabre_DAV_Exception
     */
    public function testUpdateProperties() {
        
        $principal = new Sabre_DAV_Auth_Principal('principals/admin');
        $principal->updateProperties(array('{DAV:}yourmom'=>'test'));

    }

}
