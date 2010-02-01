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
     * Returns a list of calendars for a users' uri 
     *
     * The uri is not a full path, just the actual last part
     * 
     * @param string $userUri 
     * @return array 
     */
    abstract function getCalendarsForUser($userUri);

    /**
     * Creates a new calendar for a user
     *
     * The userUri and calendarUri are not full paths, just the 'basename'.
     *
     * @param string $userUri
     * @param string $calendarUri
     * @param string $displayName
     * @param string $description
     * @return void
     */
    abstract function createCalendar($userUri,$calendarUri,$displayName,$description); 

    /**
     * Updates a calendar's basic information 
     * 
     * @param string $calendarId
     * @param string $displayName 
     * @param string $description 
     * @return void
     */
    abstract function updateCalendar($calendarId,$displayName,$description);

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
     * @param mixed $calendarId 
     * @param string $objectUri 
     * @return array 
     */
    abstract function getCalendarObject($calendarId,$objectUri);

    /**
     * Creates a new calendar object. 
     * 
     * @param mixed $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    abstract function createCalendarObject($calendarId,$objectUri,$calendarData);

    /**
     * Updates an existing calendarobject, based on it's uri. 
     * 
     * @param mixed $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    abstract function updateCalendarObject($calendarId,$objectUri,$calendarData);

    /**
     * Deletes an existing calendar object. 
     * 
     * @param mixed $calendarId 
     * @param string $objectUri 
     * @return void
     */
    abstract function deleteCalendarObject($calendarId,$objectUri);

}
