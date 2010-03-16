<?php

class Sabre_CalDAV_CalendarQueryFilterTest extends PHPUnit_Framework_TestCase {

    protected function getTestCalendarData($type = 1) {

        return Sabre_CalDAV_TestUtil::getTestCalendarData($type);

    }

    protected function getTestTODO() {

        $todo = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Example Corp.//CalDAV Client//EN
BEGIN:VTODO
DTSTAMP:20060205T235335Z
DUE;VALUE=DATE:20060104
STATUS:NEEDS-ACTION
SUMMARY:Task #1
UID:DDDEEB7915FA61233B861457@example.com
BEGIN:VALARM
ACTION:AUDIO
TRIGGER;RELATED=START:-PT10M
END:VALARM
END:VTODO
END:VCALENDAR';
        
        return $todo;

    }

    function testSubStringMatchAscii() {

        $caldav = new Sabre_CalDAV_Plugin();

        $this->assertTrue($caldav->substringMatch('string','string','i;ascii-casemap'));
        $this->assertTrue($caldav->substringMatch('string','rin', 'i;ascii-casemap'));
        $this->assertTrue($caldav->substringMatch('STRING','string','i;ascii-casemap'));
        $this->assertTrue($caldav->substringMatch('string','RIN', 'i;ascii-casemap'));
        $this->assertFalse($caldav->substringMatch('string','ings', 'i;ascii-casemap'));

    }

    function testSubStringMatchOctet() {

        $caldav = new Sabre_CalDAV_Plugin();

        $this->assertTrue($caldav->substringMatch('string','string','i;octet'));
        $this->assertTrue($caldav->substringMatch('string','rin', 'i;octet'));
        $this->assertFalse($caldav->substringMatch('STRING','string','i;octet'));
        $this->assertFalse($caldav->substringMatch('string','RIN', 'i;octet'));
        $this->assertFalse($caldav->substringMatch('string','ings', 'i;octet'));

    }

    function testParseICalendarDuration() {

        $caldav = new Sabre_CalDAV_Plugin();
        $this->assertEquals('+1 weeks', $caldav->parseICalendarDuration('P1W'));
        $this->assertEquals('+5 days', $caldav->parseICalendarDuration('P5D'));
        $this->assertEquals('+5 days 3 hours 50 minutes 12 seconds', $caldav->parseICalendarDuration('P5DT3H50M12S'));
        $this->assertEquals('-1 weeks 50 minutes', $caldav->parseICalendarDuration('-P1WT50M'));
        $this->assertEquals('+50 days 3 hours 2 seconds', $caldav->parseICalendarDuration('+P50DT3H2S'));

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testParseICalendarDurationFail() {

        $caldav = new Sabre_CalDAV_Plugin();
        $caldav->parseICalendarDuration('P1X');

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testSubStringMatchUnknownCollation() {

        $caldav = new Sabre_CalDAV_Plugin();

        $caldav->substringMatch('string','string','i;bla');

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
        $expected = array(
            '/c:iCalendar/c:vcalendar' => array(),
            '/c:iCalendar/c:vcalendar/c:vevent' => array(),
        );
        

        $result = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $result);

        $this->assertTrue($calendarPlugin->validateFilters($this->getTestCalendarData(),$result));

    }


    /**
     * @depends testCompFilter
     * @depends testParseICalendarDuration
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
        $expected = array(
            '/c:iCalendar/c:vcalendar' => array(),
            '/c:iCalendar/c:vcalendar/c:vevent' => array(
                'time-range' => array(
                    'start' => new DateTime('2006-01-04 00:00:00',new DateTimeZone('UTC')),
                    'end' =>   new DateTime('2006-01-05 00:00:00',new DateTimeZone('UTC')),
                ),
            ),
        );
        

        $filters = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $filters);
        
        $this->assertFalse($calendarPlugin->validateFilters($this->getTestCalendarData(),$filters));
        $filters['/c:iCalendar/c:vcalendar/c:vevent']['time-range']['end'] = new DateTime('2011-01-01 00:00:00', new DateTimeZone('UTC'));

        foreach(range(1,7) as $testCase) {
            $this->assertTrue($calendarPlugin->validateFilters($this->getTestCalendarData($testCase),$filters));
        }

    }

    /**
     * @depends testTimeRange
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testTimeRangeNoDTSTART() {

        $calendarPlugin = new Sabre_CalDAV_Plugin(Sabre_CalDAV_Util::getBackend());

        $xml = <<<XML
<?xml version="1.0"?>
<C:filter xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <C:comp-filter name="VCALENDAR">
    <C:comp-filter name="VEVENT">
        <C:time-range start="20060104T000000Z" end="20110105T000000Z" />
    </C:comp-filter>
 </C:comp-filter>
</C:filter>
XML;


        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);
        $filters = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
       
        $calendarPlugin->validateFilters($this->getTestCalendarData('X'),$filters);

    }


    /**
     * @depends testCompFilter
     * @depends testSubStringMatchOctet
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
        $expected = array(
            '/c:iCalendar/c:vcalendar' => array(),
            '/c:iCalendar/c:vcalendar/c:vevent' => array(),
            '/c:iCalendar/c:vcalendar/c:vevent/c:uid' => array(
                'text-match' => array(
                    'collation' => 'i;octet',
                    'value' => 'DC6C50A017428C5216A2F1CD@example.com',
                    'negate-condition' => false,
                ),
            ),
        );

        $filters = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $filters);

        $this->assertFalse($calendarPlugin->validateFilters($this->getTestCalendarData(),$filters));
        $filters['/c:iCalendar/c:vcalendar/c:vevent/c:uid']['text-match']['value'] = '39A6B5ED-DD51-4AFE-A683-C35EE3749627';
        $this->assertTrue($calendarPlugin->validateFilters($this->getTestCalendarData(),$filters));


    }

    /**
     * @depends testPropFilter
     * @depends testSubStringMatchAscii
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
                <C:text-match collation="i;ascii-casemap">needs-action</C:text-match>
            </C:param-filter>
        </C:prop-filter>
    </C:comp-filter>
 </C:comp-filter>
</C:filter>
XML;


        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);
        $expected = array(
            '/c:iCalendar/c:vcalendar' => array(),
            '/c:iCalendar/c:vcalendar/c:vevent' => array(),
            '/c:iCalendar/c:vcalendar/c:vevent/c:attendee' => array(
                'text-match' => array(
                    'collation' => 'i;ascii-casemap',
                    'negate-condition' => false,
                    'value' => 'mailto:lisa@example.com',
                ),
             ),
             '/c:iCalendar/c:vcalendar/c:vevent/c:attendee/@partstat' => array(
                'text-match' => array(
                    'collation' => 'i;ascii-casemap',
                    'negate-condition' => false,
                    'value' => 'needs-action',
                ),
            ),
        );

        $result = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $result);
        $this->assertTrue($calendarPlugin->validateFilters($this->getTestCalendarData(),$result));


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
        $expected = array(
            '/c:iCalendar/c:vcalendar' => array(),
            '/c:iCalendar/c:vcalendar/c:vtodo' => array(),
            '/c:iCalendar/c:vcalendar/c:vtodo/c:completed' => array(
                'is-not-defined' => true,
            ),
            '/c:iCalendar/c:vcalendar/c:vtodo/c:status' => array(
                'text-match' => array(
                    'collation' => 'i;ascii-casemap',
                    'negate-condition' => true,
                    'value'    => 'CANCELLED',
                ),
            ),
        );

        $result = $calendarPlugin->parseCalendarQueryFilters($dom->firstChild);
        $this->assertEquals($expected, $result);
        $this->assertFalse($calendarPlugin->validateFilters($this->getTestCalendarData(),$result));
        $this->assertTrue($calendarPlugin->validateFilters($this->getTestTodo(),$result));

    }

}
