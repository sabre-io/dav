<?php

class Sabre_CalDAV_CalendarQueryFilterTest extends PHPUnit_Framework_TestCase {

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

    }
}
