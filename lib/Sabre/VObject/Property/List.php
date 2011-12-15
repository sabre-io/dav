<?php

/**
 * MultiValue property 
 *
 * This element is used for iCalendar properties such as the DTSTART property. 
 * It basically provides a few helper functions that make it easier to deal 
 * with these. It supports both DATE-TIME and DATE values.
 *
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Property_List extends Sabre_VObject_Property {
    
    const DELIMITER = ',';
    
    /**
     * implode values with ";" after calling parent::addSlashes
     *
     * @param string|array $value
     * @return string
     */
    public function addSlashes($value) {
        
        foreach ($value as &$_value) {
            $_value = parent::addSlashes($_value);
        }
        
        return implode(self::DELIMITER, $value);
    }
    
    /**
     * Updates the internal value
     *
     * @param string|array $value
     * @return void
     */
    public function setValue($value) {
    
        if (!is_array($value)) {
            $value = $this->splitCompoundValues($value, self::DELIMITER);
        }
        
        $this->value = $value;
    }
    
    /**
     * split compound value into single parts
     *
     * @param string $value
     * @param string $delimiter
     * @return array
     */
    protected function splitCompoundValues($value) {
    
        $delimiter = self::DELIMITER;
        
        // split by any $delimiter which is NOT prefixed by a slash
        $compoundValues = preg_split("/(?<!\\\)$delimiter/", $value);
    
        // remove slashes from any semicolon and comma left escaped in the single values
        foreach ($compoundValues as &$compoundValue) {
            $compoundValue = str_replace("\\;", ';', $compoundValue);
            $compoundValue = str_replace("\\,", ',', $compoundValue);
        }
    
        reset($compoundValues);
    
        return $compoundValues;
    }
    
    /**
     * Called when this object is being cast to a string
     *
     * @return string
     */
    public function __toString() {
    
        return $this->addSlashes($this->value);
    }
    
}

