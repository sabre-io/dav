<?php

/**
 * DateTimeParser
 *
 * This class is responsible for parsing the several different date and time 
 * formats iCalendar and vCards have.
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_DateTimeParser {

    /**
     * Parses an iCalendar (rfc5545) formatted datetime and returns a DateTime object
     *
     * Specifying a reference timezone is optional. It will only be used
     * if the non-UTC format is used. The argument is used as a reference, the 
     * returned DateTime object will still be in the UTC timezone.
     *
     * @param string $dt 
     * @param DateTimeZone $tz 
     * @return DateTime 
     */
    static public function parseDateTime($dt,DateTimeZone $tz = null) {

        // Format is YYYYMMDD + "T" + hhmmss 
        $result = preg_match('/^([1-3][0-9]{3})([0-1][0-9])([0-3][0-9])T([0-2][0-9])([0-5][0-9])([0-5][0-9])([Z]?)$/',$dt,$matches);

        if (!$result) {
            throw new Sabre_DAV_Exception_BadRequest('The supplied iCalendar datetime value is incorrect: ' . $dt);
        }

        if ($matches[7]==='Z' || is_null($tz)) {
            $tz = new DateTimeZone('UTC');
        } 
        $date = new DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5] .':' . $matches[6], $tz);

        // Still resetting the timezone, to normalize everything to UTC
        $date->setTimeZone(new DateTimeZone('UTC'));
        return $date;

    }

    /**
     * Parses an iCalendar (rfc5545) formatted date and returns a DateTime object
     *
     * @param string $date 
     * @return DateTime 
     */
    static public function parseDate($date) {

        // Format is YYYYMMDD
        $result = preg_match('/^([1-3][0-9]{3})([0-1][0-9])([0-3][0-9])$/',$date,$matches);

        if (!$result) {
            throw new Sabre_DAV_Exception_BadRequest('The supplied iCalendar date value is incorrect: ' . $date);
        }

        $date = new DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3], new DateTimeZone('UTC'));
        return $date;

    }

    /**
     * Parses an iCalendar (RFC5545) formatted duration value.
     *
     * This method will either return a DateTimeInterval object, or a string
     * suitable for strtotime or DateTime::modify.
     *
     * @param string $duration
     * @return DateInterval|string 
     */
    static public function parseDuration($duration, $asString = false) {

        if (!$asString) {
            $invert = false;
            if (substr($duration,0,1)==='-') {
                $invert = true;
                $duration = substr($duration,1);
            }
                // DateInterval actually supports a superset of the iCalendar 
                // duration property, so we can pass it as-is.
            $iv = new DateInterval($duration);
            if ($invert) $iv->invert = true;

            return $iv;

        }

        $result = preg_match('/^(?P<plusminus>\+|-)?P((?P<week>\d+)W)?((?P<day>\d+)D)?(T((?P<hour>\d+)H)?((?P<minute>\d+)M)?((?P<second>\d+)S)?)?$/', $duration, $matches);
        if (!$result) {
            throw new Sabre_DAV_Exception_BadRequest('The supplied iCalendar duration value is incorrect: ' . $duration);
        }
       
        $parts = array(
            'week',
            'day',
            'hour',
            'minute',
            'second',
        );

        $newDur = '';
        foreach($parts as $part) {
            if (isset($matches[$part]) && $matches[$part]) {
                $newDur.=' '.$matches[$part] . ' ' . $part . 's';
            }
        }

        $newDur = ($matches['plusminus']==='-'?'-':'+') . trim($newDur);
        return $newDur;

    }

    /**
     * Parses either a Date or DateTime, or Duration value.
     * 
     * @param string $date
     * @param DateTimeZone|string $referenceTZ
     * @return DateTime|DateInterval
     */
    static public function parse($date, $referenceTZ = null) {

        if ($date[0]==='P' || ($date[0]==='-' && $date[1]==='P')) {
            return self::parseDuration($date);
        } elseif (strlen($date)===8) {
            return self::parseDate($date);
        } else {
            return self::parseDateTime($date, $referenceTZ);
        }

    } 
   

}

?>
