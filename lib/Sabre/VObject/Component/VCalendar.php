<?php

/**
 * The VCalendar component
 *
 * This component adds functionality to a component, specific for a VCALENDAR.
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Component_VCalendar extends Sabre_VObject_Component {

    /**
     * If this calendar object, has events with recurrence rules, this method 
     * can be used to expand the event into multiple sub-events.
     *
     * Each event will be stripped from it's recurrence information, and only 
     * the instances of the event in the specified timerange will be left 
     * alone.
     *
     * This method will alter the VCalendar. This cannot be reversed.
     *
     * This functionality is specifically used by the CalDAV standard. It is 
     * possible for clients to request expand events, if they are rather simple 
     * clients and do not have the possibility to calculate recurrences.
     *
     * @param DateTime $start
     * @param DateTime $end 
     * @return void
     */
    public function expand(DateTime $start, DateTime $end) {

        $newEvents = array();

        foreach($this->select('VEVENT') as $key=>$eventObject) {

            // unsetting the initial event
            unset($this->children[$key]); 
            
            

        }

    } 

}

?>
