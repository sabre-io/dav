<?php

/**
 * VObject Component
 *
 * This class represents a VCALENDAR/VCARD component. A component is for example
 * VEVENT, VTODO and also VCALENDAR. It starts with BEGIN:COMPONENTNAME and 
 * ends with END:COMPONENTNAME
 *
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Component extends Sabre_VObject_Element {

    /**
     * Name, for example VEVENT 
     * 
     * @var string 
     */
    public $name;

    /**
     * Children properties and components 
     * 
     * @var array
     */
    public $children = array();


    /**
     * Creates a new component.
     *
     * By default this object will iterate over its own children, but this can 
     * be overridden with the iterator argument
     * 
     * @param string $name 
     * @param Sabre_VObject_ElementList $iterator
     */
    public function __construct($name, Sabre_VObject_ElementList $iterator = null) {

        $this->name = strtoupper($name);
        if (!is_null($iterator)) $this->iterator = $iterator;

    }

    /**
     * Turns the object back into a serialized blob. 
     * 
     * @return string 
     */
    public function serialize() {

        $str = "BEGIN:" . $this->name . "\r\n";
        foreach($this->children as $child) $str.=$child->serialize();
        $str.= "END:" . $this->name . "\r\n";
        
        return $str;

    }

    /**
     * Adds a new componenten or element
     *
     * You can call this method with the following syntaxes:
     *
     * add(Sabre_VObject_Element $element)
     * add(string $name, $value)
     *
     * The first version adds an Element
     * The second adds a property as a string. 
     * 
     * @param mixed $item 
     * @param mixed $itemValue 
     * @return void
     */
    public function add($item, $itemValue = null) {

        if ($item instanceof Sabre_VObject_Element) {
            if (!is_null($itemValue)) {
                throw new InvalidArgumentException('The second argument must not be specified, when passing a VObject');
            }
            $this->children[] = $item;
        } elseif(is_string($item)) {

            if (!is_scalar($itemValue)) {
                throw new InvalidArgumentException('The second argument must be scalar');
            }
            $this->children[] = new Sabre_VObject_Property($item,$itemValue);

        } else {
            
            throw new InvalidArgumentException('The first argument must either be a Sabre_VObject_Element or a string');

        }

    }

    /**
     * Returns an iterable list of children 
     * 
     * @return Sabre_VObject_ElementList 
     */
    public function children() {

        return new Sabre_VObject_ElementList($this->children);

    }

    /* Magic property accessors {{{ */

    /**
     * Using 'get' you will either get a propery or component, 
     *
     * If there were no child-elements found with the specified name,
     * null is returned.
     * 
     * @param string $name 
     * @return void
     */
    public function __get($name) {

        $name = strtoupper($name);
        $matches = array();

        foreach($this->children as $child) {
            if ($child->name === $name)
                $matches[] = $child;
        }

        if (count($matches)===0) {
            return null;
        } else {
            $firstMatch = $matches[0];
            $firstMatch->setIterator(new Sabre_VObject_ElementList($matches));
            return $firstMatch;
        }

    }

    /**
     * This method checks if a sub-element with the specified name exists. 
     * 
     * @param string $name 
     * @return bool 
     */
    public function __isset($name) {

        $name = strtoupper($name);

        foreach($this->children as $child) {

            if ($child->name === $name) 
                return true;

        }
        return false;

    }

    /**
     * Using the setter method you can add properties or subcomponents
     *
     * You can either pass a Sabre_VObject_Component, Sabre_VObject_Property
     * object, or a string to automatically create a Property.
     *
     * If the item already exists, it will be removed. If you want to add
     * a new item with the same name, always use the add() method.
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value) {

        $name = strtoupper($name);
        $arrayKey = null;
        foreach($this->children as $key=>$child) {

            if ($child->name == $name) {
                $arrayKey = $key;
                break;
            }

        }

        if ($value instanceof Sabre_VObject_Component || $value instanceof Sabre_VObject_Property) {
            if (!is_null($arrayKey)) {
                $this->children[$arrayKey] = $value;
            } else {
                $this->children[] = $value;
            }
        } elseif (is_scalar($value)) {
            if (!is_null($arrayKey)) {
                $this->children[$arrayKey] = new Sabre_VObject_Property($name,$value);
            } else {
                $this->children[] = new Sabre_VObject_Property($name,$value);
            }
        } else {
            throw new InvalidArgumentException('You must pass a Sabre_VObject_Component, Sabre_VObject_Property or scalar type');
        }

    }

    /**
     * Removes all properties and components within this component. 
     * 
     * @param string $name 
     * @return void
     */
    public function __unset($name) {

        $name = strtoupper($name);

        foreach($this->children as $k=>$child) {

            if ($child->name === $name) 
                unset($this->children[$k]);

        }

    }

    /* }}} */


}
