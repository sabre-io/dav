<?php

/**
 * VObject Property
 *
 * A property in VObject is usually in the form PARAMNAME:paramValue.
 * An example is : SUMMARY:Weekly meeting 
 *
 * Properties can also have parameters:
 * SUMMARY;LANG=en:Weekly meeting.
 *
 * Parameters can be accessed using the ArrayAccess interface. 
 *
 * @package Sabre
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Property extends Sabre_VObject_Element {

    /**
     * Propertyname 
     * 
     * @var string 
     */
    public $name;

    /**
     * Property parameters 
     * 
     * @var array 
     */
    public $parameters = array();

    /**
     * Property value 
     * 
     * @var string 
     */
    public $value;

    /**
     * Creates a new property object
     * 
     * By default this object will iterate over its own children, but this can 
     * be overridden with the iterator argument
     * 
     * @param string $name 
     * @param string $value
     * @param Sabre_VObject_ElementList $iterator
     */
    public function __construct($name, $value = null, $iterator = null) {

        $this->name = strtoupper($name);
        $this->value = $value;
        if (!is_null($iterator)) $this->iterator = $iterator;

    }

    /**
     * Turns the object back into a serialized blob. 
     * 
     * @return string 
     */
    public function serialize() {

        $str = $this->name;
        if (count($this->parameters)) {
            foreach($this->parameters as $param) {
                
                $str.=';' . $param->serialize();

            }
        }
        $src = array(
            '\\',
            "\n",
        );
        $out = array(
            '\\\\',
            '\n',
        );
        $str.=':' . str_replace($src, $out, $this->value);

        $out = '';
        while(strlen($str)>0) {
            if (strlen($str)>75) {
                $out.= substr($str,0,75) . "\r\n";
                $str = ' ' . substr($str,75);
            } else {
                $out.=$str . "\r\n";
                $str='';
                break;
            }
        }

        return $out;

    }

    /* ArrayAccess interface {{{ */

    /**
     * Checks if an array element exists
     * 
     * @param mixed $name 
     * @return bool 
     */
    public function offsetExists($name) {

        if (is_int($name)) return parent::offsetExists($name);

        $name = strtoupper($name);

        foreach($this->parameters as $parameter) {
            if ($parameter->name == $name) return true;
        }
        return false;

    }

    /**
     * Returns a parameter, or parameter list. 
     * 
     * @param string $name 
     * @return Sabre_VObject_Element 
     */
    public function offsetGet($name) {

        if (is_int($name)) return parent::offsetGet($name);
        $name = strtoupper($name);
        
        $result = array();
        foreach($this->parameters as $parameter) {
            if ($parameter->name == $name)
                $result[] = $parameter;
        }

        if (count($result)===0) {
            return null;
        } elseif (count($result)===1) {
            return $result[0];
        } else {
            return new Sabre_VObject_ElementList($result);
        }

    }

    /**
     * Creates a new parameter 
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function offsetSet($name, $value) {

        if (is_int($name)) return parent::offsetSet($name, $value);
        if (is_scalar($value)) {
            if (!is_string($name)) 
                throw new InvalidArgumentException('A parameter name must be specified. This means you cannot use the $array[]="string" to add parameters.');

            $this->parameters[] = new Sabre_VObject_Parameter($name, $value);
        } elseif ($value instanceof Sabre_VObject_Parameter) {
            if (!is_null($name))
                throw new InvalidArgumentException('Don\'t specify a parameter name if you\'re passing a Sabre_VObject_Parameter. Add using $array[]=$parameterObject.');
            
            $this->parameters[] = $value;
        } else {
            throw new InvalidArgumentException('You can only add parameters to the property object');
        }

    }

    /**
     * Removes one or more parameters with the specified name 
     * 
     * @param string $name 
     * @return void 
     */
    public function offsetUnset($name) {

        if (is_int($name)) return parent::offsetUnset($name, $value);
        $name = strtoupper($name);
        
        $result = array();
        foreach($this->parameters as $key=>$parameter) {
            if ($parameter->name == $name) {
                unset($this->parameters[$key]);
            }

        }

    }

    /* }}} */

    /**
     * Called when this object is being cast to a string 
     * 
     * @return string 
     */
    public function __toString() {

        return $this->value;

    }


}
