<?php

/**
 * VObject ElementList
 *
 * This class represents a list of elements. Lists are the result of queries,
 * such as doing $vcalendar->vevent where there's multiple VEVENT objects.
 *
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_ElementList extends Sabre_VObject_Element implements Iterator {

    /**
     * Inner elements 
     * 
     * @var array
     */
    protected $elements = array();

    /**
     * Creates the element list.
     *
     * @param array $elements 
     */
    public function __construct(array $elements) {

        $this->elements = $elements;

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

        return $this->elements[$this->key];

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

        return isset($this->elements[$this->key]);

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
     * We use 'get' to forward any requests for properties
     * to the currently selected object in the iteration.
     *
     * @param string $name 
     * @return void
     */
    public function __get($name) {

        if (isset($this->elements[$this->key]->$name)) {
            return $this->elements[$this->key]->$name;
        }

        return null;

    }

    /**
     * This method checks if a sub-element with the specified name exists. 
     * 
     * @param string $name 
     * @return bool 
     */
    public function __isset($name) {

        return isset($this->elements[$this->key]->$name);

    }

    /* }}} */
}
