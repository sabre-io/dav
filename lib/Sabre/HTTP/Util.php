<?php

/**
 * HTTP utility methods 
 * 
 * @package Sabre
 * @subpackage HTTP
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_HTTP_Util {

    /**
     * Parses a RFC2616-compatible date string
     *
     * This method returns false if the date is invalid
     * 
     * @param string $dateHeader 
     * @return bool|DateTime 
     */
    static function parseRFC2616Date($dateHeader) {

        $patterns = array(
            // Matches: Sun, 06 Nov 1994 08:49:37 GMT
            '%a, %d %h %Y %H:%M:%S GMT',
            // Matches: Sunday, 06-Nov-94 08:49:37 GMT
            '%A, %d-%h-%y %H:%M:%S GMT',
            // Matches: Sun Nov  6 08:49:37 1994
            '%a %h %e %H:%M:%S %Y'
         );

        $realDate = 0;
        foreach($patterns as $pattern) {

            if ($date_arr = strptime($dateHeader,$pattern)) {
                $realDate = strtotime($dateHeader);
                break;
            }
        
        }

        // Unknown format
        if (!$realDate) { 

            return false;

        }


        return new DateTime('@' . $realDate, new DateTimeZone('UTC'));

    }

}
