<?php

class Sabre_DAVACL_PrincipalTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {

        $principal = new Sabre_DAVACL_Principal(array('uri' => 'principals/admin'));
        $this->assertTrue($principal instanceof Sabre_DAVACL_Principal);

    }

    /**
     * @expectedException Sabre_DAV_Exception
     */
    public function testConstructNoUri() {

        $principal = new Sabre_DAVACL_Principal(array());

    }

    public function testGetName() {

        $principal = new Sabre_DAVACL_Principal(array('uri' => 'principals/admin'));
        $this->assertEquals('admin',$principal->getName());

    }

    public function testGetDisplayName() {

        $principal = new Sabre_DAVACL_Principal(array('uri' => 'principals/admin'));
        $this->assertEquals('admin',$principal->getDisplayname());

        $principal = new Sabre_DAVACL_Principal(array(
            'uri' => 'principals/admin',
            '{DAV:}displayname' => 'Mr. Admin'
        ));
        $this->assertEquals('Mr. Admin',$principal->getDisplayname());

    }

    public function testGetProperties() {

        $principal = new Sabre_DAVACL_Principal(array(
            'uri' => 'principals/admin',
            '{DAV:}displayname' => 'Mr. Admin',
            '{http://www.example.org/custom}custom' => 'Custom',
            '{http://sabredav.org/ns}email-address' => 'admin@example.org',
        ));

        $keys = array(
            '{DAV:}displayname',
            '{http://www.example.org/custom}custom',
            '{http://sabredav.org/ns}email-address',
        );
        $props = $principal->getProperties($keys);

        foreach($keys as $key) $this->assertArrayHasKey($key,$props);

        $this->assertEquals('Mr. Admin',$props['{DAV:}displayname']);

        $this->assertEquals('admin@example.org', $props['{http://sabredav.org/ns}email-address']);
    }

    public function testUpdateProperties() {
        
        $principal = new Sabre_DAVACL_Principal(array('uri' => 'principals/admin'));
        $result = $principal->updateProperties(array('{DAV:}yourmom'=>'test'));
        $this->assertEquals(false,$result);

    }

}
