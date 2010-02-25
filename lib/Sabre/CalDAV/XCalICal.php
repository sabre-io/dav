<?php

/**
 * This script is used to convert ICalendar (rfc2445) objects XCal-Basic
 * (draft-royer-calsch-xcal-03) format. 
 *
 * Properties are converted to lowercase xml elements. Parameters are
 * converted to attributes. BEGIN:VEVENT is converted to <vevent> and
 * END:VEVENT </vevent> as well as other components.
 *
 * It's a very loose parser. If any line does not conform to the spec, it
 * will simply be ignored. It will try to detect if \r\n or \n line endings
 * are used.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 * @see http://tools.ietf.org/html/draft-royer-calsch-xcal-03
 */
class Sabre_CalDAV_XCalICal {

    /**
     * Converts ICalendar data to XML. 
     *
     * @todo Currently quoted attributes are not parsed correctly.
     * @param string $icalData 
     * @return string. 
     */
    static function toXCAL($icalData) {

        // Detecting line endings
        $lb="\r\n";
        if (strpos($icalData,"\r\n")!==false) $lb = "\r\n";
        elseif (strpos($icalData,"\n")!==false) $lb = "\n";

        // Splitting up items per line
        $lines = explode($lb,$icalData);

        // Properties can be folded over 2 lines. In this case the second
        // line will be preceeded by a space or tab.
        $lines2 = array();
        foreach($lines as $line) {

            if (!$line) continue;
            if ($line[0]===" " || $line[0]==="\t") {
                $lines2[count($lines2)-1].=substr($line,1);
                continue;
            }

            $lines2[]=$line;

        }

        $xml = '<?xml version="1.0"?>' . "\n";
        $xml.= "<iCalendar xmlns=\"urn:ietf:params:xml:ns:xcal\">\n";

        $spaces = 2;
        foreach($lines2 as $line) {

            $matches = array();
            // This matches PROPERTYNAME;ATTRIBUTES:VALUE
            if (!preg_match('/^([^:^;]*)(?:;([^:]*))?:(.*)$/',$line,$matches))
                continue;

            $propertyName = strtolower($matches[1]);
            $attributes = $matches[2];
            $value = $matches[3];

            // If the line was in the format BEGIN:COMPONENT or END:COMPONENT, we need to special case it.
            if ($propertyName === 'begin') {
                $xml.=str_repeat(" ",$spaces);
                $xml.='<' . strtolower($value) . ">\n";
                $spaces+=2;
                continue;
            } elseif ($propertyName === 'end') {
                $spaces-=2;
                $xml.=str_repeat(" ",$spaces);
                $xml.='</' . strtolower($value) . ">\n";
                continue;
            }

            $xml.=str_repeat(" ",$spaces);
            $xml.='<' . $propertyName;
            if ($attributes) {
                // There can be multiple attributes
                $attributes = explode(';',$attributes);
                foreach($attributes as $att) {
  
                    list($attName,$attValue) = explode('=',$att,2);
                    $attName = strtolower($attName);
                    if ($attName === 'language') $attName='xml:lang';
                    $xml.=' ' . $attName . '="' . htmlspecialchars($attValue) . '"';

                }
            }

            $xml.='>'. htmlspecialchars($value) . '</' . $propertyName . ">\n";
          
        }
        $xml.="</iCalendar>";
        return $xml;

    }

}

