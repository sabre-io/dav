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
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Component extends Sabre_VObject_Element implements Iterator {

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
     * Creates a new component 
     * 
     * @param string $name 
     */
    public function __construct($name) {

        $this->name = strtoupper($name);

    }

    /* {{{ Iterator interface */

    /**
     * Current position  
     * 
     * @var int 
     */
    private $key = 0;

    /**
     * Returns current item in iteration 
     * 
     * @return Sabre_VObject_Element 
     */
    public function current() {

        return $this->children[$this->key];

    }
   
    /**
     * To the next item in the iterator 
     * 
     * @return void
     */
    public function next() {

        $this->key++;

    }

    /**
     * Returns the current iterator key 
     * 
     * @return int
     */
    public function key() {

        return $this->key;

    }

    /**
     * Returns true if the current position in the iterator is a valid one 
     * 
     * @return bool 
     */
    public function valid() {

        return isset($this->children[$this->key]);

    }

    /**
     * Rewinds the iterator 
     * 
     * @return void 
     */
    public function rewind() {

        $this->key = 0;

    }

    /* }}} */

    /* Magic property accessors {{{ */

    /**
     * Using 'get' you will either get a propery or component, 
     * or: if there's multiple matches for a property/componentname an
     * Sabre_VObject_ElementList.
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
        } elseif (count($matches) === 1) {
            return $matches[0];
        } else {
            return new Sabre_VObject_ElementList($matches);
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
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value) {

        if ($value instanceof Sabre_VObject_Component ||
          $value instanceof Sabre_VObject_Property) {
            $this->children[] = $value;
        } elseif (is_scalar($value)) {
            $this->children[] = new Sabre_VObject_Property($name,$value);
        } else {
            throw new InvalidArgumentException('You must pass a Sabre_VObject_Component, Sabre_VObject_Property or scalar type');
        }

    }

    /* }}} */

}
