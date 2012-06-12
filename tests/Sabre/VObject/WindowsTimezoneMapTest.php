<?php

class Sabre_VObject_WindowsTimezoneMapTest extends PHPUnit_Framework_TestCase {

    /**
     * @dataProvider getMapping
     */
    function testCorrectTZ($timezoneName) {

        $tz = new DateTimeZone($timezoneName);

    }

    function getMapping() {

        // PHPUNit requires an array of arrays
        return array_map(
            function($value) {
                return array($value);
            },
            Sabre_VObject_WindowsTimeZoneMap::$map
        );

    }

}
