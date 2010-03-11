<?php

class Sabre_CalDAV_CalendarQueryFilterTest extends PHPUnit_Framework_TestCase {

    protected function getTestCalendarData() {

        $calendarData = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 4.0.1//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Asia/Seoul
BEGIN:DAYLIGHT
TZOFFSETFROM:+0900
RRULE:FREQ=YEARLY;UNTIL=19880507T150000Z;BYMONTH=5;BYDAY=2SU
DTSTART:19870510T000000
TZNAME:GMT+09:00
TZOFFSETTO:+1000
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1000
DTSTART:19881009T000000
TZNAME:GMT+09:00
TZOFFSETTO:+0900
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20100225T154229Z
UID:39A6B5ED-DD51-4AFE-A683-C35EE3749627
DTEND;TZID=Asia/Seoul:20100223T070000
TRANSP:TRANSPARENT
SUMMARY:Something here
DTSTART;TZID=Asia/Seoul:20100223T060000
DTSTAMP:20100228T130202Z
SEQUENCE:2
END:VEVENT
END:VCALENDAR';

        return $calendarData;

    }

    function testCompFilter() {

        $calendarPlugin = new Sabre_CalDAV_Plugin(Sabre_CalDAV_Util::getBackend());

        $xml = <<<XML
<?xml version="1.0"?>
<C:filter xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <C:comp-filter name="VCALENDAR">
   <C:comp-filter name="VEVENT" />
 </C:comp-filter>
</C:filter>
XML;


        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);
        $expected = array(array(
            'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
            'name' => 'VCALENDAR',
            'isnotdefined' => false,
            'filters' => array(
                array(
                    'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
                    'name' => 'VEVENT',
                    'isnotdefined' => false,
                    'filters' => array(),
                ),
            ),
        ));
        

        $result = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $result);

        $this->assertTrue($calendarPlugin->validateFilters($this->getTestCalendarData(),$result));

    }


    /**
     * @depends testCompFilter
     */
    function testTimeRange() {

        $calendarPlugin = new Sabre_CalDAV_Plugin(Sabre_CalDAV_Util::getBackend());

        $xml = <<<XML
<?xml version="1.0"?>
<C:filter xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <C:comp-filter name="VCALENDAR">
    <C:comp-filter name="VEVENT">
        <C:time-range start="20060104T000000Z" end="20060105T000000Z" />
    </C:comp-filter>
 </C:comp-filter>
</C:filter>
XML;


        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);
        $expected = array(array(
            'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
            'name' => 'VCALENDAR',
            'isnotdefined' => false,
            'filters' => array(
                array(
                    'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
                    'name' => 'VEVENT',
                    'isnotdefined' => false,
                    'filters' => array(
                        array(
                            'type' => Sabre_CalDAV_Plugin::FILTER_TIMERANGE,
                            'start' => '20060104T000000Z',
                            'end' => '20060105T000000Z',
                        ),
                    ),
                ),
            ),
        ));
        

        $result = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $result);
        $this->assertFalse($calendarPlugin->validateFilters($this->getTestCalendarData(),$result));

    }


    /**
     * @depends testCompFilter
     */
    function testPropFilter() {

        $calendarPlugin = new Sabre_CalDAV_Plugin(Sabre_CalDAV_Util::getBackend());

        $xml = <<<XML
<?xml version="1.0"?>
<C:filter xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <C:comp-filter name="VCALENDAR">
    <C:comp-filter name="VEVENT">
        <C:prop-filter name="UID">
            <C:text-match collation="i;octet">DC6C50A017428C5216A2F1CD@example.com</C:text-match>
        </C:prop-filter>
    </C:comp-filter>
 </C:comp-filter>
</C:filter>
XML;


        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);
        $expected = array(array(
            'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
            'name' => 'VCALENDAR',
            'isnotdefined' => false,
            'filters' => array(
                array(
                    'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
                    'name' => 'VEVENT',
                    'isnotdefined' => false,
                    'filters' => array(
                        array(
                            'type' => Sabre_CalDAV_Plugin::FILTER_PROPFILTER,
                            'name' => 'UID',
                            'isnotdefined' => false,
                            'filters' => array(
                                array(
                                    'type' => Sabre_CalDAV_Plugin::FILTER_TEXTMATCH,
                                    'collation' => 'i;octet',
                                    'value' => 'DC6C50A017428C5216A2F1CD@example.com',
                                    'negate-condition' => false,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ));


        $result = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $result);
        $this->assertFalse($calendarPlugin->validateFilters($this->getTestCalendarData(),$result));

    }

    /**
     * @depends testPropFilter
     */
    function testParamFilter() {

        $calendarPlugin = new Sabre_CalDAV_Plugin(Sabre_CalDAV_Util::getBackend());

        $xml = <<<XML
<?xml version="1.0"?>
<C:filter xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <C:comp-filter name="VCALENDAR">
    <C:comp-filter name="VEVENT">
        <C:prop-filter name="ATTENDEE">
            <C:text-match collation="i;ascii-casemap">mailto:lisa@example.com</C:text-match>
            <C:param-filter name="PARTSTAT">
                <C:text-match collation="i;ascii-casemap">NEEDS-ACTION</C:text-match>
            </C:param-filter>
        </C:prop-filter>
    </C:comp-filter>
 </C:comp-filter>
</C:filter>
XML;


        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);
        $expected = array(array(
            'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
            'name' => 'VCALENDAR',
            'isnotdefined' => false,
            'filters' => array(
                array(
                    'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
                    'name' => 'VEVENT',
                    'isnotdefined' => false,
                    'filters' => array(
                        array(
                            'type' => Sabre_CalDAV_Plugin::FILTER_PROPFILTER,
                            'name' => 'ATTENDEE',
                            'isnotdefined' => false,
                            'filters' => array(
                                array(
                                    'type' => Sabre_CalDAV_Plugin::FILTER_TEXTMATCH,
                                    'collation' => 'i;ascii-casemap',
                                    'negate-condition' => false,
                                    'value' => 'mailto:lisa@example.com',
                                ),
                                array(
                                    'type' => Sabre_CalDAV_Plugin::FILTER_PARAMFILTER,
                                    'name' => 'PARTSTAT',
                                    'isnotdefined' => false,
                                    'filters' => array(
                                        array(
                                            'type' => Sabre_CalDAV_Plugin::FILTER_TEXTMATCH,
                                            'collation' => 'i;ascii-casemap',
                                            'negate-condition' => false,
                                            'value' => 'NEEDS-ACTION',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ));

        $result = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $result);
        $this->assertFalse($calendarPlugin->validateFilters($this->getTestCalendarData(),$result));


    }

    /**
     * @depends testParamFilter
     */
    function testUndefinedNegation() {

        $calendarPlugin = new Sabre_CalDAV_Plugin(Sabre_CalDAV_Util::getBackend());

        $xml = <<<XML
<?xml version="1.0"?>
<C:filter xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <C:comp-filter name="VCALENDAR">
    <C:comp-filter name="VTODO">
        <C:prop-filter name="COMPLETED">
            <C:is-not-defined />
        </C:prop-filter>
        <C:prop-filter name="STATUS">
            <C:text-match negate-condition="yes">CANCELLED</C:text-match>
        </C:prop-filter>
    </C:comp-filter>
 </C:comp-filter>
</C:filter>
XML;


        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);
        $expected = array(array(
            'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
            'name' => 'VCALENDAR',
            'isnotdefined' => false,
            'filters' => array(
                array(
                    'type' => Sabre_CalDAV_Plugin::FILTER_COMPFILTER,
                    'name' => 'VTODO',
                    'isnotdefined' => false,
                    'filters' => array(
                        array(
                            'type' => Sabre_CalDAV_Plugin::FILTER_PROPFILTER,
                            'name' => 'COMPLETED',
                            'isnotdefined' => true,
                        ),
                        array(
                            'type' => Sabre_CalDAV_Plugin::FILTER_PROPFILTER,
                            'name' => 'STATUS',
                            'isnotdefined' => false,
                            'filters' => array(
                                array(
                                    'type' => Sabre_CalDAV_Plugin::FILTER_TEXTMATCH,
                                    'collation' => 'i;ascii-casemap',
                                    'negate-condition' => true,
                                    'value' => 'CANCELLED',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ));

        $result = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $result);
        $this->assertFalse($calendarPlugin->validateFilters($this->getTestCalendarData(),$result));

    }
}
