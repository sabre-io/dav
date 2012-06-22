<?php

namespace Sabre\VObject;

class WindowsTimezoneMapTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider getMapping
     */
    function testCorrectTZ($timezoneName) {

        $tz = new \DateTimeZone($timezoneName);

    }

    function getMapping() {

        // PHPUNit requires an array of arrays
        return array_map(
            function($value) {
                return array($value);
            },
            WindowsTimezoneMap::$map
        );

    }

}
