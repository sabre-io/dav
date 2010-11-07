<?php

class Sabre_VObject_Parameter extends Sabre_VObject_Element {

    public $name;
    public $value;

    public function __construct($name, $value = null) {

        $this->name = strtoupper($name);
        $this->value = $value;

    } 

}
