<?php

/**
 * The VCalendar component
 *
 * This component adds functionality to a component, specific for a VCALENDAR.
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
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
     * In addition, this method will cause timezone information to be stripped, 
     * and normalized to UTC.
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

        foreach($this->select('VEVENT') as $key=>$vevent) {

            unset($this->children[$key]);

            if (!$vevent->rrule) {
                if ($vevent->isInTimeRange($start, $end)) {
                    $newEvents[] = $vevent;
                }
                continue;
            }
            
            $it = new Sabre_VObject_RecurrenceIterator($vevent);
            $it->fastForward($start);

            while($it->getDTStart() < $end) {

                if ($it->getDTEnd() > $start) {

                    $newVEvent = clone $vevent;
                    $newVEvent->DTSTART->setDateTime($it->getDTStart(), $newVEvent->DTSTART->getDateType());

                    // We only need to update DTEND if it was set in the 
                    // original. Otherwise there was no DTEND at all, or a 
                    // DURATION property. 
                    if (isset($newVEvent->DTEND)) {
                        $newVEvent->DTEND->setDateTime($it->getDTEnd(), $newVEvent->DTSTART->getDateType());
                    }

                    // We need to add the RECURRENCE-ID property, unless the 
                    // event is the 'first' event in sequence.
                    if ($it->getDTStart() != $vevent->DTSTART->getDateTime()) {
                        $newVEvent->{'RECURRENCE-ID'} = (string)$newVEvent->DTSTART;
                    }

                    $newEvents[] = $newVEvent;

                }
                $it->next();

            }

        }

        foreach($newEvents as $newEvent) {

            // Final cleanup
            unset($newEvent->RRULE);
            unset($newEvent->EXDATE);
            unset($newEvent->EXRULE);
            unset($newEvent->RDATE);

            // Setting all date and time properties to UTC
            foreach($newEvent->children() as $child) {
                if ($child instanceof Sabre_VObject_Property_DateTime &&
                    $child->getDateType() == Sabre_VObject_Property_DateTime::LOCALTZ) {
                        $child->setDateTime($child->getDateTime(),Sabre_VObject_Property_DateTime::UTC);
                    }
            }

            $this->add($newEvent);

        }

        // Removing all VTIMEZONE components
        unset($this->VTIMEZONE);

    } 

}

