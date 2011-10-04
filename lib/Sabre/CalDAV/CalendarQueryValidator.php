<?php

/**
 * CalendarQuery Validator
 *
 * This class is responsible for checking if an iCalendar object matches a set 
 * of filters. The main function to do this is 'validate'.
 *
 * This is used to determine which icalendar objects should be returned for a 
 * calendar-query REPORT request. 
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_CalendarQueryValidator {

    /**
     * Verify if a list of filters applies to the calendar data object 
     *
     * The calendarData object must be a valid iCalendar blob. The list of 
     * filters must be formatted as parsed by Sabre_CalDAV_CalendarQueryParser
     *
     * @param string $calendarData 
     * @param array $filters 
     * @return bool 
     */
    public function validate($calendarData,array $filters) {

        $vObject = Sabre_VObject_Reader::read($calendarData);

        // The top level object is always a component filter.
        // We'll parse it manually, as it's pretty simple.
        if ($vObject->name !== $filters['name']) {
            return false;
        }

        return 
            $this->validateCompFilters($vObject, $filters['comp-filters']) &&
            $this->validatePropFilters($vObject, $filters['prop-filters']);


    }

    /**
     * This method checks the validity of comp-filters.
     *
     * A list of comp-filters needs to be specified. Also the parent of the 
     * component we're checking should be specified, not the component to check 
     * itself.
     * 
     * @param Sabre_VObject_Component $parent
     * @param array $filters 
     * @return bool 
     */
    protected function validateCompFilters(Sabre_VObject_Component $parent, array $filters) {

        foreach($filters as $filter) {

            $isDefined = isset($parent->$filter['name']);

            if ($filter['is-not-defined']) {

                if ($isDefined) { 
                    return false;
                } else { 
                    continue;
                }

            }
            if (!$isDefined) {
                return false;
            }

            if ($filter['time-range']) {
                throw new Sabre_DAV_Exception_NotImplemented('Time-range is not yet implemented');
            }

            if (!$filter['comp-filters'] && !$filter['prop-filters']) {
                continue;
            }

            // If there are sub-filters, we need to find at least one component 
            // for which the subfilters hold true.
            foreach($parent->$filter['name'] as $subComponent) {

                if (
                    $this->validateCompFilters($subComponent, $filter['comp-filters']) &&
                    $this->validatePropFilters($subComponent, $filter['prop-filters'])) {
                        // We had a match, so this comp-filter succeeds
                        continue 2;
                }

            }

            // If we got here it means there were sub-comp-filters or 
            // sub-prop-filters and there was no match. This means this filter 
            // needs to return false.
            return false;

        }

        // If we got here it means we got through all comp-filters alive so the 
        // filters were all true.
        return true; 

    }

    /**
     * This method checks the validity of prop-filters.
     *
     * A list of prop-filters needs to be specified. Also the parent of the 
     * property we're checking should be specified, not the property to check 
     * itself.
     * 
     * @param Sabre_VObject_Component $parent
     * @param array $filters 
     * @return bool 
     */
    protected function validatePropFilters(Sabre_VObject_Component $parent, array $filters) {

        foreach($filters as $filter) {

            $isDefined = isset($parent->$filter['name']);

            if ($filter['is-not-defined']) {

                if ($isDefined) { 
                    return false;
                } else { 
                    continue;
                }

            }
            if (!$isDefined) {
                return false;
            }

            if ($filter['time-range']) {
                throw new Sabre_DAV_Exception_NotImplemented('Time-range is not yet implemented');
            }

            if (!$filter['param-filters'] && !$filter['text-match']) {
                continue;
            }

            // If there are sub-filters, we need to find at least one property 
            // for which the subfilters hold true.
            foreach($parent->$filter['name'] as $subComponent) {

                if(
                    $this->validateParamFilters($subComponent, $filter['param-filters']) &&
                    (!$filter['text-match'] || $this->validateTextMatch($subComponent, $filter['text-match']))
                ) { 
                    // We had a match, so this prop-filter succeeds
                    continue 2;
                }

            }

            // If we got here it means there were sub-param-filters or 
            // text-match filters and there was no match. This means the 
            // filter needs to return false. 
            return false;

        }

        // If we got here it means we got through all prop-filters alive so the 
        // filters were all true.
        return true; 

    }

    /**
     * This method checks the validity of param-filters.
     *
     * A list of param-filters needs to be specified. Also the parent of the 
     * parameter we're checking should be specified, not the parameter to check 
     * itself.
     * 
     * @param Sabre_VObject_Property $parent
     * @param array $filters 
     * @return bool 
     */
    protected function validateParamFilters(Sabre_VObject_Property $parent, array $filters) {

        foreach($filters as $filter) {

            $isDefined = isset($parent[$filter['name']]);

            if ($filter['is-not-defined']) {

                if ($isDefined) { 
                    return false;
                } else { 
                    continue;
                }

            }
            if (!$isDefined) {
                return false;
            }

            if (!$filter['text-match']) {
                continue;
            }

            // If there are sub-filters, we need to find at least one parameter 
            // for which the subfilters hold true.
            foreach($parent[$filter['name']] as $subParam) {

                if($this->validateTextMatch($subParam,$filter['text-match'])) {
                    // We had a match, so this param-filter succeeds
                    continue 2;
                }

            }

            // If we got here it means there was a text-match filter and there 
            // were no matches. This means the filter needs to return false.
            return false;

        }

        // If we got here it means we got through all param-filters alive so the 
        // filters were all true.
        return true; 

    }

    /**
     * This method checks the validity of a text-match.
     *
     * A single text-match should be specified as well as the specific property 
     * or parameter we need to validate.
     * 
     * @param Sabre_VObject_Element $parent
     * @param array $filters 
     * @return bool 
     */
    protected function validateTextMatch(Sabre_VObject_Node $parent, array $textMatch) {

        $value = (string)$parent;

        $isMatching = Sabre_DAV_StringUtil::textMatch($value, $textMatch['value'], $textMatch['collation']);

        return ($textMatch['negate-condition'] xor $isMatching);

    }

    /*


            $elem = $xml->xpath($xpath);
            
            if (!$elem) return false;
            $elem = $elem[0];

            if (isset($filter['time-range'])) {

                switch($elem->getName()) {
                    case 'vevent' :
                        $result = $this->validateTimeRangeFilterForEvent($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'vtodo' :
                        $result = $this->validateTimeRangeFilterForTodo($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'vjournal' :
                    case 'vfreebusy' :
                    case 'valarm' :
                        // TODO: not implemented
                        break;

                    / *

                    case 'vjournal' :
                        $result = $this->validateTimeRangeFilterForJournal($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'vfreebusy' :
                        $result = $this->validateTimeRangeFilterForFreeBusy($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'valarm' :
                        $result = $this->validateTimeRangeFilterForAlarm($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;

                        * /

                }

            } 

            if (isset($filter['text-match'])) {
                $currentString = (string)$elem;

                $isMatching = Sabre_DAV_StringUtil::textMatch($currentString, $filter['text-match']['value'], $filter['text-match']['collation']);
                if ($filter['text-match']['negate-condition'] && $isMatching) return false;
                if (!$filter['text-match']['negate-condition'] && !$isMatching) return false;
                
            }

        }
        return true;
        
    }

     */

    /**
     * Checks whether a time-range filter matches an event.
     * 
     * @param SimpleXMLElement $xml Event as xml object 
     * @param string $currentXPath XPath to check 
     * @param array $currentFilter Filter information 
     * @return void
     */
    /*
    private function validateTimeRangeFilterForEvent(SimpleXMLElement $xml,$currentXPath,array $currentFilter) {

        // Grabbing the DTSTART property
        $xdtstart = $xml->xpath($currentXPath.'/c:dtstart');
        if (!count($xdtstart)) {
            throw new Sabre_DAV_Exception_BadRequest('DTSTART property missing from calendar object');
        }

        // The dtstart can be both a date, or datetime property
        if ((string)$xdtstart[0]['value']==='DATE' || strlen((string)$xdtstart[0])===8) {
            $isDateTime = false;
        } else {
            $isDateTime = true;
        }

        // Determining the timezone
        if ($tzid = (string)$xdtstart[0]['tzid']) {
            $tz = new DateTimeZone($tzid);
        } else {
            $tz = null;
        }
        if ($isDateTime) {
            $dtstart = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdtstart[0],$tz);
        } else {
            $dtstart = Sabre_CalDAV_XMLUtil::parseICalendarDate((string)$xdtstart[0]);
        }


        // Grabbing the DTEND property
        $xdtend = $xml->xpath($currentXPath.'/c:dtend');
        $dtend = null;

        if (count($xdtend)) {
            // Determining the timezone
            if ($tzid = (string)$xdtend[0]['tzid']) {
                $tz = new DateTimeZone($tzid);
            } else {
                $tz = null;
            }

            // Since the VALUE prameter of both DTSTART and DTEND must be the same
            // we can assume we don't need to check the VALUE paramter of DTEND.
            if ($isDateTime) {
                $dtend = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdtend[0],$tz);
            } else {
                $dtend = Sabre_CalDAV_XMLUtil::parseICalendarDate((string)$xdtend[0],$tz);
            }

        } 
        
        if (is_null($dtend)) {
            // The DTEND property was not found. We will first see if the event has a duration
            // property

            $xduration = $xml->xpath($currentXPath.'/c:duration');
            if (count($xduration)) {
                $duration = Sabre_CalDAV_XMLUtil::parseICalendarDuration((string)$xduration[0]);

                // Making sure that the duration is bigger than 0 seconds.
                $tempDT = clone $dtstart;
                $tempDT->modify($duration);
                if ($tempDT > $dtstart) {

                    // use DTEND = DTSTART + DURATION 
                    $dtend = $tempDT;
                } else {
                    // use DTEND = DTSTART
                    $dtend = $dtstart;
                }

            }
        }

        if (is_null($dtend)) {
            if ($isDateTime) {
                // DTEND = DTSTART
                $dtend = $dtstart;
            } else {
                // DTEND = DTSTART + 1 DAY
                $dtend = clone $dtstart;
                $dtend->modify('+1 day');
            }
        }
        // TODO: we need to properly parse RRULE's, but it's very difficult.
        // For now, we're always returning events if they have an RRULE at all.
        $rrule = $xml->xpath($currentXPath.'/c:rrule');
        $hasRrule = (count($rrule))>0; 
       
        if (!is_null($currentFilter['time-range']['start']) && $currentFilter['time-range']['start'] >= $dtend)  return false;
        if (!is_null($currentFilter['time-range']['end'])   && $currentFilter['time-range']['end']   <= $dtstart && !$hasRrule) return false;
        return true;
    
    }

    private function validateTimeRangeFilterForTodo(SimpleXMLElement $xml,$currentXPath,array $filter) {

        // Gathering all relevant elements

        $dtStart = null;
        $duration = null;
        $due = null;
        $completed = null;
        $created = null;

        $xdt = $xml->xpath($currentXPath.'/c:dtstart');
        if (count($xdt)) {
            // The dtstart can be both a date, or datetime property
            if ((string)$xdt[0]['value']==='DATE') {
                $isDateTime = false;
            } else {
                $isDateTime = true;
            }

            // Determining the timezone
            if ($tzid = (string)$xdt[0]['tzid']) {
                $tz = new DateTimeZone($tzid);
            } else {
                $tz = null;
            }
            if ($isDateTime) {
                $dtStart = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdt[0],$tz);
            } else {
                $dtStart = Sabre_CalDAV_XMLUtil::parseICalendarDate((string)$xdt[0]);
            }
        }

        // Only need to grab duration if dtStart is set
        if (!is_null($dtStart)) {

            $xduration = $xml->xpath($currentXPath.'/c:duration');
            if (count($xduration)) {
                $duration = Sabre_CalDAV_XMLUtil::parseICalendarDuration((string)$xduration[0]);
            }

        }

        if (!is_null($dtStart) && !is_null($duration)) {

            // Comparision from RFC 4791:
            // (start <= DTSTART+DURATION) AND ((end > DTSTART) OR (end >= DTSTART+DURATION))

            $end = clone $dtStart;
            $end->modify($duration);

            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $end) &&
                (is_null($filter['time-range']['end']) || $filter['time-range']['end'] > $dtStart || $filter['time-range']['end'] >= $end) ) {
                return true;
            } else {
                return false;
            }

        }

        // Need to grab the DUE property
        $xdt = $xml->xpath($currentXPath.'/c:due');
        if (count($xdt)) {
            // The due property can be both a date, or datetime property
            if ((string)$xdt[0]['value']==='DATE') {
                $isDateTime = false;
            } else {
                $isDateTime = true;
            }
            // Determining the timezone
            if ($tzid = (string)$xdt[0]['tzid']) {
                $tz = new DateTimeZone($tzid);
            } else {
                $tz = null;
            }
            if ($isDateTime) {
                $due = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdt[0],$tz);
            } else {
                $due = Sabre_CalDAV_XMLUtil::parseICalendarDate((string)$xdt[0]);
            }
        }

        if (!is_null($dtStart) && !is_null($due)) {

            // Comparision from RFC 4791:
            // ((start < DUE) OR (start <= DTSTART)) AND ((end > DTSTART) OR (end >= DUE))
            
            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] < $due || $filter['time-range']['start'] < $dtstart) &&
                (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $due) ) {
                return true;
            } else {
                return false;
            }

        }

        if (!is_null($dtStart)) {
            
            // Comparision from RFC 4791
            // (start <= DTSTART)  AND (end > DTSTART)
            if ( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $dtStart) &&
                 (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] > $dtStart) ) {
                 return true;
            } else {
                return false;
            }

        }

        if (!is_null($due)) {
            
            // Comparison from RFC 4791
            // (start < DUE) AND (end >= DUE)
            if ( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] < $due) &&
                 (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $due) ) {
                 return true;
            } else {
                return false;
            }

        }
        // Need to grab the COMPLETED property
        $xdt = $xml->xpath($currentXPath.'/c:completed');
        if (count($xdt)) {
            $completed = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdt[0]);
        }
        // Need to grab the CREATED property
        $xdt = $xml->xpath($currentXPath.'/c:created');
        if (count($xdt)) {
            $created = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdt[0]);
        }

        if (!is_null($completed) && !is_null($created)) {
            // Comparison from RFC 4791
            // ((start <= CREATED) OR (start <= COMPLETED)) AND ((end >= CREATED) OR (end >= COMPLETED))
            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $created || $filter['time-range']['start'] <= $completed) &&
                (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $created   || $filter['time-range']['end'] >= $completed)) {
                return true;
            } else {
                return false;
            }
        }

        if (!is_null($completed)) {
            // Comparison from RFC 4791
            // (start <= COMPLETED) AND (end  >= COMPLETED)
            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $completed) &&
                (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $completed)) {
                return true;
            } else {
                return false;
            }
        }

        if (!is_null($created)) {
            // Comparison from RFC 4791
            // (end > CREATED)
            if( (is_null($filter['time-range']['end']) || $filter['time-range']['end'] > $created) ) {
                return true;
            } else {
                return false;
            }
        }

        // Everything else is TRUE
        return true;

    }*/

}

?>
