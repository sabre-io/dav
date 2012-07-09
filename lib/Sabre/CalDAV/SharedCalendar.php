<?php

/**
 * This object represents a CalDAV calendar that is shared by a different user.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_SharedCalendar extends Sabre_CalDAV_Calendar implements Sabre_CalDAV_ISharedCalendar {

    /**
     * This method should return the url of the owners' copy of the shared
     * calendar.
     *
     * @return string
     */
    public function getSharedUrl() {

        return $this->calendarProperties['{http://calendarserver.org/ns/}shared-url'];

    }

}
