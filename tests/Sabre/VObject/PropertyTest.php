<?php

class Sabre_VObject_PropertyTest extends PHPUnit_Framework_TestCase {

    public function testToString() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $this->assertEquals('PROPNAME', $property->name);
        $this->assertEquals('propvalue', $property->value);
        $this->assertEquals('propvalue', $property->__toString());
        $this->assertEquals('propvalue', (string)$property);

    }

    public function testParameterExists() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $property->parameters[] = new Sabre_VObject_Parameter('paramname','paramvalue');

        $this->assertTrue(isset($property['PARAMNAME']));
        $this->assertTrue(isset($property['paramname']));
        $this->assertFalse(isset($property['foo']));

    }

    public function testParameterGet() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $property->parameters[] = new Sabre_VObject_Parameter('paramname','paramvalue');
        
        $this->assertInstanceOf('Sabre_VObject_Parameter',$property['paramname']);

    }

    public function testParameterNotExists() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $property->parameters[] = new Sabre_VObject_Parameter('paramname','paramvalue');
        
        $this->assertInternalType('null',$property['foo']);

    }

    public function testParameterMultiple() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $property->parameters[] = new Sabre_VObject_Parameter('paramname','paramvalue');
        $property->parameters[] = new Sabre_VObject_Parameter('paramname','paramvalue');
        
        $this->assertInstanceOf('Sabre_VObject_ElementList',$property['paramname']);
        $this->assertEquals(2,count($property['paramname']));

    }

    public function testAddParameterAsString() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $property['paramname'] = 'paramvalue';

        $this->assertEquals(1,count($property->parameters));
        $this->assertInstanceOf('Sabre_VObject_Parameter', $property->parameters[0]);
        $this->assertEquals('PARAMNAME',$property->parameters[0]->name);
        $this->assertEquals('paramvalue',$property->parameters[0]->value);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddParameterAsStringNoKey() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $property[] = 'paramvalue';

    }

    public function testAddParameterObject() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $param = new Sabre_VObject_Parameter('paramname','paramvalue');

        $property[] = $param;

        $this->assertEquals(1,count($property->parameters));
        $this->assertEquals($param, $property->parameters[0]);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddParameterObjectWithKey() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $param = new Sabre_VObject_Parameter('paramname','paramvalue');

        $property['key'] = $param;

    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddParameterObjectRandomObject() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $property[] = new StdClass(); 

    }

    public function testUnsetParameter() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $param = new Sabre_VObject_Parameter('paramname','paramvalue');
        $property->parameters[] = $param;

        unset($property['PARAMNAME']);
        $this->assertEquals(0,count($property->parameters));

    }

    public function testParamCount() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $param = new Sabre_VObject_Parameter('paramname','paramvalue');
        $property->parameters[] = $param;
        $property->parameters[] = clone $param;

        $this->assertEquals(2,count($property->parameters));

    }

    public function testSerialize() {

        $property = new Sabre_VObject_Property('propname','propvalue');

        $this->assertEquals("PROPNAME:propvalue\r\n",$property->serialize());

    }

    public function testSerializeParam() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $property->parameters[] = new Sabre_VObject_Parameter('paramname','paramvalue');
        $property->parameters[] = new Sabre_VObject_Parameter('paramname2','paramvalue2');

        $this->assertEquals("PROPNAME;PARAMNAME=paramvalue;PARAMNAME2=paramvalue2:propvalue\r\n",$property->serialize());

    }

    public function testSerializeNewLine() {

        $property = new Sabre_VObject_Property('propname',"line1\nline2");

        $this->assertEquals("PROPNAME:line1\\nline2\r\n",$property->serialize());

    }

    public function testSerializeLongLine() {

        $value = str_repeat('!',200);
        $property = new Sabre_VObject_Property('propname',$value);

        $expected = "PROPNAME:" . str_repeat('!',66) . "\r\n " . str_repeat('!',74) . "\r\n " . str_repeat('!',60) . "\r\n";

        $this->assertEquals($expected,$property->serialize());

    }

    public function testGetIterator() {

        $it = new Sabre_VObject_ElementList(array());
        $property = new Sabre_VObject_Property('propname','propvalue', $it);
        $this->assertEquals($it,$property->getIterator());

    }


    public function testGetIteratorDefault() {

        $property = new Sabre_VObject_Property('propname','propvalue');
        $it = $property->getIterator();
        $this->assertTrue($it instanceof Sabre_VObject_ElementList);
        $this->assertEquals(1,count($it));

    }
}
