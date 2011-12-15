<?php

require_once 'Sabre/CalDAV/TestUtil.php';

class Sabre_VObject_DateTimeParserTest extends PHPUnit_Framework_TestCase {

    function testParseICalendarDuration() {

        $this->assertEquals('+1 weeks', Sabre_VObject_DateTimeParser::parseDuration('P1W',true));
        $this->assertEquals('+5 days',  Sabre_VObject_DateTimeParser::parseDuration('P5D',true));
        $this->assertEquals('+5 days 3 hours 50 minutes 12 seconds', Sabre_VObject_DateTimeParser::parseDuration('P5DT3H50M12S',true));
        $this->assertEquals('-1 weeks 50 minutes', Sabre_VObject_DateTimeParser::parseDuration('-P1WT50M',true));
        $this->assertEquals('+50 days 3 hours 2 seconds', Sabre_VObject_DateTimeParser::parseDuration('+P50DT3H2S',true));

    }

    function testParseICalendarDurationDateInterval() {

        $expected = new DateInterval('P7D');
        $this->assertEquals($expected, Sabre_VObject_DateTimeParser::parseDuration('P1W'));
        $this->assertEquals($expected, Sabre_VObject_DateTimeParser::parse('P1W'));

        $expected = new DateInterval('P3M');
        $expected->invert = true;
        $this->assertEquals($expected, Sabre_VObject_DateTimeParser::parseDuration('-P3M'));

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testParseICalendarDurationFail() {

        Sabre_VObject_DateTimeParser::parseDuration('P1X',true);

    }

    function testParseICalendarDateTime() {

        $dateTime = Sabre_VObject_DateTimeParser::parseDateTime('20100316T141405');

        $compare = new DateTime('2010-03-16 14:14:05',new DateTimeZone('UTC'));

        $this->assertEquals($compare, $dateTime);

    }

    /**
     * @depends testParseICalendarDateTime
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testParseICalendarDateTimeBadFormat() {

        $dateTime = Sabre_VObject_DateTimeParser::parseDateTime('20100316T141405 ');

    }

    /**
     * @depends testParseICalendarDateTime
     */
    function testParseICalendarDateTimeUTC() {

        $dateTime = Sabre_VObject_DateTimeParser::parseDateTime('20100316T141405Z');

        $compare = new DateTime('2010-03-16 14:14:05',new DateTimeZone('UTC'));
        $this->assertEquals($compare, $dateTime);

    }

    /**
     * @depends testParseICalendarDateTime
     */
    function testParseICalendarDateTimeUTC2() {

        $dateTime = Sabre_VObject_DateTimeParser::parseDateTime('20101211T160000Z');

        $compare = new DateTime('2010-12-11 16:00:00',new DateTimeZone('UTC'));
        $this->assertEquals($compare, $dateTime);

    }

    /**
     * @depends testParseICalendarDateTime
     */
    function testParseICalendarDateTimeCustomTimeZone() {

        $dateTime = Sabre_VObject_DateTimeParser::parseDateTime('20100316T141405', new DateTimeZone('Europe/Amsterdam'));

        $compare = new DateTime('2010-03-16 13:14:05',new DateTimeZone('UTC'));
        $this->assertEquals($compare, $dateTime);

    }

    function testParseICalendarDate() {

        $dateTime = Sabre_VObject_DateTimeParser::parseDate('20100316');

        $expected = new DateTime('2010-03-16 00:00:00',new DateTimeZone('UTC'));

        $this->assertEquals($expected, $dateTime);

        $dateTime = Sabre_VObject_DateTimeParser::parse('20100316');
        $this->assertEquals($expected, $dateTime);

    }

    /**
     * @depends testParseICalendarDate
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testParseICalendarDateBadFormat() {

        $dateTime = Sabre_VObject_DateTimeParser::parseDate('20100316T141405');

    }
}
