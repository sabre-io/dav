<?php

class Sabre_VObject_Reader {

    static function read($data) {

        // Detecting line endings
        if (strpos($data,"\r\n")!==false) {
            $newLine = "\r\n";
        } elseif (strpos($data,"\r")) {
            $newLine = "\r";
        } else {
            $newLine = "\n";
        }

        $lines = explode($newLine, $data);

        // Unfolding lines
        $lines2 = array();
        foreach($lines as $line) {

            if ($line[0]===" " || $line[0]==="\t") {
                $lines2[count($lines2)-1].=substr($line,1);
            } else {
                $lines2[] = $line;
            }

        }

        unset($lines);
        
        reset($lines2);

        return self::readLine($lines2);
       
    }

    static private function readLine(&$lines) {

        $line = current($lines);
        $lineNr = key($lines);
        next($lines);

        // Components
        if (stripos($line,"BEGIN:")===0) {

            // This is a component
            $obj = new Sabre_VObject_Component();
            $obj->name = strtoupper(substr($line,6));

            $nextLine = current($lines);
            while(stripos($nextLine,"END:")!==0) {

                $obj->children[] = self::readLine($lines);
                $nextLine = current($lines);

                if ($nextLine===false) 
                    throw new Sabre_VObject_ParseException('Invalid VObject. Document ended prematurely.');

            }

            // Checking component name of the 'END:' line. 
            if (substr($nextLine,4)!==$obj->name) {
                throw new Sabre_VObject_ParseException('Invalid VObject, expected: "END:' . $obj->name . '" got: "' . $nextLine . '"');
            }

            return $obj;

        }

        // Properties
        //$result = preg_match('/(?P<name>[A-Z0-9-]+)(?:;(?P<attributes>^(?<!:):))(.*)$/',$line,$matches);
        $result = preg_match('/^(?P<name>[A-Z0-9-]+):(?P<value>.*)$/i',$line,$matches);

        if (!$result) {
            throw new Sabre_VObject_ParseException('Invalid VObject, line ' . ($lineNr+1) . ' did not follow icalendar format');
        }

        $obj = new Sabre_VObject_Property();
        $obj->name = strtoupper($matches['name']);
        $obj->value = $matches['value'];

        return $obj;


    }


}
