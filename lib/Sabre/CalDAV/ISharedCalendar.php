<?php

/**
 * This interface represents a Calendar that is shared by a different user.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_CalDAV_ISharedCalendar extends Sabre_CalDAV_ICalendar {

    /**
     * This method should return the url of the owners' copy of the shared
     * calendar.
     *
     * @return string
     */
    function getSharedUrl();

}
