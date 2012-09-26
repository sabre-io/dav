<?php

class Sabre_Util_UtilTest extends PHPUnit_Framework_TestCase {

    function testParseHTTPDate() {

        $times = array(
            'Wed, 13 Oct 2010 10:26:00 GMT',
            'Wednesday, 13-Oct-10 10:26:00 GMT',
            'Wed Oct 13 10:26:00 2010',
        );

        $expected = 1286965560;

        foreach($times as $time) {
            $result = Sabre_HTTP_Util::parseHTTPDate($time);
            $this->assertEquals($expected, $result->format('U'));
        }

        $result = Sabre_HTTP_Util::parseHTTPDate('Wed Oct  6 10:26:00 2010');
        $this->assertEquals(1286360760, $result->format('U'));

    }

    function testParseHTTPDateFail() {

        $times = array(
            //random string
            'NOW',
            // not-GMT timezone
            'Wednesday, 13-Oct-10 10:26:00 UTC',
            // No space before the 6
            'Wed Oct 6 10:26:00 2010',
        );

        foreach($times as $time) {
            $this->assertFalse(Sabre_HTTP_Util::parseHTTPDate($time), 'We used the string: ' . $time);
        }

    }

    function testTimezones() {

        $default = date_default_timezone_get();
        date_default_timezone_set('Europe/Amsterdam');

        $this->testParseHTTPDate();

        date_default_timezone_set($default);

    }

    function testToHTTPDate() {

        $dt = new DateTime('2011-12-10 12:00:00 +0200');

        $this->assertEquals(
            'Sat, 10 Dec 2011 10:00:00 GMT',
            Sabre_HTTP_Util::toHTTPDate($dt)
        );

    }

    function testStrtotimeFail() {

        // Strtotime may return -1 when the date cannot be parsed.
        // We are simulating this situation by testing a date that actually
        // results in -1. (because I have found no other way to break this
        // code)

        $time = 'Wed, 13 Oct 1960 10:26:00 GMT';

        $this->assertNull(Sabre_HTTP_Util::parseHTTPDate($time));

    }
}
