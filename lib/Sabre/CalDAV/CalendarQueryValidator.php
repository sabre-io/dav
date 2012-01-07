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
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_CalendarQueryValidator {

    /**
     * Verify if a list of filters applies to the calendar data object
     *
     * The list of filters must be formatted as parsed by Sabre_CalDAV_CalendarQueryParser
     *
     * @param Sabre_VObject_Component $vObject
     * @param array $filters
     * @return bool
     */
    public function validate(Sabre_VObject_Component $vObject,array $filters) {

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
                foreach($parent->$filter['name'] as $subComponent) {
                    if ($this->validateTimeRange($subComponent, $filter['time-range']['start'], $filter['time-range']['end'])) {
                        continue 2;
                    }
                }
                return false;
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
                foreach($parent->$filter['name'] as $subComponent) {
                    if ($this->validateTimeRange($subComponent, $filter['time-range']['start'], $filter['time-range']['end'])) {
                        continue 2;
                    }
                }
                return false;
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
     * @param Sabre_VObject_Node $parent
     * @param array $textMatch
     * @return bool
     */
    protected function validateTextMatch(Sabre_VObject_Node $parent, array $textMatch) {

        $value = (string)$parent;

        $isMatching = Sabre_DAV_StringUtil::textMatch($value, $textMatch['value'], $textMatch['collation']);

        return ($textMatch['negate-condition'] xor $isMatching);

    }

    /**
     * Validates if a component matches the given time range.
     *
     * This is all based on the rules specified in rfc4791, which are quite
     * complex.
     *
     * @param Sabre_VObject_Node $component
     * @param DateTime $start
     * @param DateTime $end
     * @return bool
     */
    protected function validateTimeRange(Sabre_VObject_Node $component, $start, $end) {

        if (is_null($start)) {
            $start = new DateTime('1900-01-01');
        }
        if (is_null($end)) {
            $end = new DateTime('3000-01-01');
        }

        switch($component->name) {

            case 'VEVENT' :
            case 'VTODO' :
            case 'VJOURNAL' :

                return $component->isInTimeRange($start, $end);

            case 'VFREEBUSY' :
                throw new Sabre_DAV_Exception_NotImplemented('time-range filters are currently not supported on ' . $component->name . ' components');

            case 'VALARM' :
                $trigger = $component->TRIGGER;
                if(!isset($trigger['TYPE']) || strtoupper($trigger['TYPE']) === 'DURATION') {
                    $triggerDuration = Sabre_VObject_DateTimeParser::parseDuration($component->TRIGGER);
                    $related = (isset($trigger['RELATED']) && strtoupper($trigger['RELATED']) == 'END') ? 'END' : 'START';

                    $parentComponent = $component->parent;
                    if ($related === 'START') {
                        $effectiveTrigger = clone $parentComponent->DTSTART->getDateTime();
                        $effectiveTrigger->add($triggerDuration);
                    } else {
                        if ($parentComponent->name === 'VTODO') {
                            $endProp = 'DUE';
                        } elseif ($parentComponent->name === 'VEVENT') {
                            $endProp = 'DTEND';
                        } else {
                            throw new Sabre_DAV_Exception('time-range filters on VALARM components are only supported when they are a child of VTODO or VEVENT');
                        }

                        if (isset($parentComponent->$endProp)) {
                            $effectiveTrigger = clone $parentComponent->$endProp->getDateTime();
                            $effectiveTrigger->add($triggerDuration);
                        } elseif (isset($parentComponent->DURATION)) {
                            $effectiveTrigger = clone $parentComponent->DTSTART->getDateTime();
                            $duration = Sabre_VObject_DateTimeParser::parseDuration($parentComponent->DURATION);
                            $effectiveTrigger->add($duration);
                            $effectiveTrigger->add($triggerDuration);
                        } else {
                            $effectiveTrigger = clone $parentComponent->DTSTART->getDateTime();
                            $effectiveTrigger->add($triggerDuration);
                        }
                    }
                } else {
                    $effectiveTrigger = $trigger->getDateTime();
                }

                if (isset($component->DURATION)) {
                    $duration = Sabre_VObject_DateTimeParser::parseDuration($component->DURATION);
                    $repeat = (string)$component->repeat;
                    if (!$repeat) {
                        $repeat = 1;
                    }

                    $period = new DatePeriod($effectiveTrigger, $duration, (int)$repeat);

                    foreach($period as $occurrence) {

                        if ($start <= $occurrence && $end > $occurrence) {
                            return true;
                        }
                    }
                    return false;
                } else {
                    return ($start <= $effectiveTrigger && $end > $effectiveTrigger);
                }
                break;

            case 'COMPLETED' :
            case 'CREATED' :
            case 'DTEND' :
            case 'DTSTAMP' :
            case 'DTSTART' :
            case 'DUE' :
            case 'LAST-MODIFIED' :
                return ($start <= $component->getDateTime() && $end >= $component->getDateTime());

            default :
                throw new Sabre_DAV_Exception_BadRequest('You cannot create a time-range filter on a ' . $component->name . ' component');

        }

    }

}
