<?php

/**
 * Abstract Calendaring backend. Extend this class to create your own backends.
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_CalDAV_Backend_Abstract {

    /**
     * Returns a list of calendars for a principal
     *
     * @param string $userUri 
     * @return array 
     */
    abstract function getCalendarsForUser($principalUri);

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return mixed
     */
    abstract function createCalendar($principalUri,$calendarUri,array $properties); 

    /**
     * Updates a calendar's properties
     *
     *
     * The mutations array has 3 elements for each item. The first indicates if the property
     * is to be removed or updated (Sabre_DAV_Server::PROP_REMOVE and Sabre_DAV_Server::PROP_SET)
     * the second is the propertyName in Clark notation, the third is the actual value (ommitted
     * if the property is to be deleted).
     *
     * The result of this method should be another array. Each element has 2 subelements with the 
     * propertyname and statuscode for the change
     *
     * For example:
     *   array(array('{DAV:}prop1',200), array('{DAV:}prop2',200), array('{DAV:}prop3',403))
     *
     * The default implementation does not allow any properties to be updated, and thus
     * will return 403 for each one.
     *
     * @param string $calendarId
     * @param array $mutations
     * @return array 
     */
    public function updateCalendar($calendarId,array $mutations) {
        
        $response = array();

        foreach($mutations as $mutation) {
            $response[] = array($mutation[1],403);
        }
        
        return $response;

    }

    /**
     * Delete a calendar and all it's objects 
     * 
     * @param string $calendarId 
     * @return void
     */
    abstract function deleteCalendar($calendarId);

    /**
     * Returns all calendar objects within a calendar object. 
     * 
     * @param string $calendarId 
     * @return array 
     */
    abstract function getCalendarObjects($calendarId);

    /**
     * Returns information from a single calendar object, based on it's object uri. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @return array 
     */
    abstract function getCalendarObject($calendarId,$objectUri);

    /**
     * Creates a new calendar object. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    abstract function createCalendarObject($calendarId,$objectUri,$calendarData);

    /**
     * Updates an existing calendarobject, based on it's uri. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    abstract function updateCalendarObject($calendarId,$objectUri,$calendarData);

    /**
     * Deletes an existing calendar object. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @return void
     */
    abstract function deleteCalendarObject($calendarId,$objectUri);

}
