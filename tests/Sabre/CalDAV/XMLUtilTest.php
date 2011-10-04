<?php

require_once 'Sabre/CalDAV/TestUtil.php';

class Sabre_CalDAV_XMLUtilTest extends PHPUnit_Framework_TestCase {

    function testParseICalendarDuration() {

        $this->assertEquals('+1 weeks', Sabre_CalDAV_XMLUtil::parseICalendarDuration('P1W'));
        $this->assertEquals('+5 days',  Sabre_CalDAV_XMLUtil::parseICalendarDuration('P5D'));
        $this->assertEquals('+5 days 3 hours 50 minutes 12 seconds', Sabre_CalDAV_XMLUtil::parseICalendarDuration('P5DT3H50M12S'));
        $this->assertEquals('-1 weeks 50 minutes', Sabre_CalDAV_XMLUtil::parseICalendarDuration('-P1WT50M'));
        $this->assertEquals('+50 days 3 hours 2 seconds', Sabre_CalDAV_XMLUtil::parseICalendarDuration('+P50DT3H2S'));

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testParseICalendarDurationFail() {

        Sabre_CalDAV_XMLUtil::parseICalendarDuration('P1X');

    }

    function testParseICalendarDateTime() {

        $dateTime = Sabre_CalDAV_XMLUtil::parseICalendarDateTime('20100316T141405');

        $compare = new DateTime('2010-03-16 14:14:05',new DateTimeZone('UTC'));

        $this->assertEquals($compare, $dateTime);

    }

    /** 
     * @depends testParseICalendarDateTime
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testParseICalendarDateTimeBadFormat() {

        $dateTime = Sabre_CalDAV_XMLUtil::parseICalendarDateTime('20100316T141405 ');

    }

    /** 
     * @depends testParseICalendarDateTime
     */
    function testParseICalendarDateTimeUTC() {

        $dateTime = Sabre_CalDAV_XMLUtil::parseICalendarDateTime('20100316T141405Z');

        $compare = new DateTime('2010-03-16 14:14:05',new DateTimeZone('UTC'));
        $this->assertEquals($compare, $dateTime);

    }

    /** 
     * @depends testParseICalendarDateTime
     */
    function testParseICalendarDateTimeUTC2() {

        $dateTime = Sabre_CalDAV_XMLUtil::parseICalendarDateTime('20101211T160000Z');

        $compare = new DateTime('2010-12-11 16:00:00',new DateTimeZone('UTC'));
        $this->assertEquals($compare, $dateTime);

    }

    /** 
     * @depends testParseICalendarDateTime
     */
    function testParseICalendarDateTimeCustomTimeZone() {

        $dateTime = Sabre_CalDAV_XMLUtil::parseICalendarDateTime('20100316T141405', new DateTimeZone('Europe/Amsterdam'));

        $compare = new DateTime('2010-03-16 13:14:05',new DateTimeZone('UTC'));
        $this->assertEquals($compare, $dateTime);

    }

    function testParseICalendarDate() {

        $dateTime = Sabre_CalDAV_XMLUtil::parseICalendarDate('20100316');

        $compare = new DateTime('2010-03-16 00:00:00',new DateTimeZone('UTC'));

        $this->assertEquals($compare, $dateTime);

    }

    /** 
     * @depends testParseICalendarDate
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testParseICalendarDateBadFormat() {

        $dateTime = Sabre_CalDAV_XMLUtil::parseICalendarDate('20100316T141405');

    }
}
