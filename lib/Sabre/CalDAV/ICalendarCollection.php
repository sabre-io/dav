<?php

/**
 * The ICalendarCollection interface needs to be implemented by any
 * collection of ICalendar objects. 
 *
 * This allows the use of the MKCALENDAR HTTP method
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_CalDAV_ICalendarCollection extends Sabre_DAV_IDirectory { 

    /**
     * This method creates a new calendar.
     *
     * The properties argument is a list of new properties.
     * 
     * @param string $name 
     * @param array $properties 
     * @return void
     */
    function createCalendar($name,$properties);
    
}
