<?php

class Sabre_VObject_ElementListTest extends PHPUnit_Framework_TestCase {

    function testIterate() {

        $elems = array();

        $sub = new Sabre_VObject_Component('VEVENT');

        $elems = array(
            $sub,
            clone $sub,
            clone $sub
        );

        $elemList = new Sabre_VObject_ElementList($elems);

        $count = 0;
        foreach($elemList as $key=>$subcomponent) {

           $count++;
           $this->assertType('Sabre_VObject_Component',$subcomponent);

        }
        $this->assertEquals(3,$count);
        $this->assertEquals(2,$key);

    }

    
    function testMagicGet() {

        $elems = array();

        $sub = new Sabre_VObject_Component('VEVENT');

        $elems = array(
            $sub,
            clone $sub,
            clone $sub
        );

        $elemList = new Sabre_VObject_ElementList($elems);

        $this->assertEquals('VEVENT', $elemList->name);
        $this->assertType('null', $elemList->foo);

    }

    function testMagicIsset() {

        $elems = array();

        $sub = new Sabre_VObject_Component('VEVENT');

        $elems = array(
            $sub,
            clone $sub,
            clone $sub
        );

        $elemList = new Sabre_VObject_ElementList($elems);

        $this->assertTrue(isset($elemList->name));
        $this->assertFalse(isset($elemList->foo));

    }

}
