<?php

/**
 * Base class for all elements
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_VObject_Element {

    /**
     * Turns the object back into a serialized blob. 
     * 
     * @return string 
     */
    abstract function serialize();


}
