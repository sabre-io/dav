<?php

class Sabre_VObject_ComponentTest extends PHPUnit_Framework_TestCase {

    function testIterate() {

        $comp = new Sabre_VObject_Component('VCALENDAR');
        
        $sub = new Sabre_VObject_Component('VEVENT');
        $comp->children[] = $sub;

        $sub = new Sabre_VObject_Component('VTODO');
        $comp->children[] = $sub;

        $count = 0;
        foreach($comp as $key=>$subcomponent) {

           $count++;
           $this->assertType('Sabre_VObject_Component',$subcomponent);

        }
        $this->assertEquals(2,$count);
        $this->assertEquals(1,$key);

    }

    function testMagicGet() {

        $comp = new Sabre_VObject_Component('VCALENDAR');
        
        $sub = new Sabre_VObject_Component('VEVENT');
        $comp->children[] = $sub;

        $sub = new Sabre_VObject_Component('VTODO');
        $comp->children[] = $sub;

        $event = $comp->vevent;
        $this->assertType('Sabre_VObject_Component', $event);
        $this->assertEquals('VEVENT', $event->name);

        $this->assertType('null', $comp->vjournal);

    }

    function testMagicIsset() {

        $comp = new Sabre_VObject_Component('VCALENDAR');
        
        $sub = new Sabre_VObject_Component('VEVENT');
        $comp->children[] = $sub;

        $sub = new Sabre_VObject_Component('VTODO');
        $comp->children[] = $sub;

        $this->assertTrue(isset($comp->vevent));
        $this->assertTrue(isset($comp->vtodo));
        $this->assertFalse(isset($comp->vjournal));

    }

    function testMagicSetScalar() {

        $comp = new Sabre_VObject_Component('VCALENDAR');
        $comp->myProp = 'myValue';

        $this->assertType('Sabre_VObject_Property',$comp->MYPROP); 
        $this->assertEquals('myValue',$comp->MYPROP->value); 

    }

    function testMagicSetComponent() {

        $comp = new Sabre_VObject_Component('VCALENDAR');

        // Note that 'myProp' is ignored here.
        $comp->myProp = new Sabre_VObject_Component('VEVENT');

        $this->assertEquals(1, count($comp->children));

        $this->assertEquals('VEVENT',$comp->VEVENT->name); 

    }

    function testMagicSetTwice() {

        $comp = new Sabre_VObject_Component('VCALENDAR');

        $comp->VEVENT = new Sabre_VObject_Component('VEVENT');
        $comp->VEVENT = new Sabre_VObject_Component('VEVENT');

        $this->assertEquals(1, count($comp->children));

        $this->assertEquals('VEVENT',$comp->VEVENT->name); 

    }

    function testAddScalar() {

        $comp = new Sabre_VObject_Component('VCALENDAR');

        $comp->add('myprop','value');

        $this->assertEquals(1, count($comp->children));

        $this->assertTrue($comp->children[0] instanceof Sabre_VObject_Property);
        $this->assertEquals('MYPROP',$comp->children[0]->name); 
        $this->assertEquals('value',$comp->children[0]->value); 

    }

    function testAddComponent() {

        $comp = new Sabre_VObject_Component('VCALENDAR');

        $comp->add(new Sabre_VObject_Component('VEVENT'));

        $this->assertEquals(1, count($comp->children));

        $this->assertEquals('VEVENT',$comp->VEVENT->name); 

    }

    function testAddComponentTwice() {

        $comp = new Sabre_VObject_Component('VCALENDAR');

        $comp->add(new Sabre_VObject_Component('VEVENT'));
        $comp->add(new Sabre_VObject_Component('VEVENT'));

        $this->assertEquals(2, count($comp->children));

        $this->assertEquals('VEVENT',$comp->VEVENT->name); 

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testAddArgFail() {

        $comp = new Sabre_VObject_Component('VCALENDAR');
        $comp->add(new Sabre_VObject_Component('VEVENT'),'hello');

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testAddArgFail2() {

        $comp = new Sabre_VObject_Component('VCALENDAR');
        $comp->add(array());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testAddArgFail3() {

        $comp = new Sabre_VObject_Component('VCALENDAR');
        $comp->add('hello',array());

    }

    /**
     * @expectedException InvalidArgumentException 
     */
    function testMagicSetInvalid() {

        $comp = new Sabre_VObject_Component('VCALENDAR');

        // Note that 'myProp' is ignored here.
        $comp->myProp = new StdClass();

        $this->assertEquals(1, count($comp->children));

        $this->assertEquals('VEVENT',$comp->VEVENT->name); 

    }

    function testCount() {

        $comp = new Sabre_VObject_Component('VCALENDAR');

        // Note that 'myProp' is ignored here.
        $comp->children = array(
            new Sabre_VObject_Component('VEVENT'),
            new Sabre_VObject_Component('VTODO')
        );

        $this->assertEquals(2,$comp->count());
        $this->assertEquals(2,count($comp));

    }

    function testChildren() {

        $comp = new Sabre_VObject_Component('VCALENDAR');

        // Note that 'myProp' is ignored here.
        $comp->children = array(
            new Sabre_VObject_Component('VEVENT'),
            new Sabre_VObject_Component('VTODO')
        );

        $r = $comp->children();
        $this->assertTrue($r instanceof Sabre_VObject_ElementList);
        $this->assertEquals(2,count($r));
    }

    function testSerialize() {

        $comp = new Sabre_VObject_Component('VCALENDAR');
        $this->assertEquals("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n", $comp->serialize());


    }

    function testSerializeChildren() {

        $comp = new Sabre_VObject_Component('VCALENDAR');
        $comp->children = array(
            new Sabre_VObject_Component('VEVENT'),
            new Sabre_VObject_Component('VTODO')
        );
        $this->assertEquals("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nEND:VEVENT\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n", $comp->serialize());


    }

}
