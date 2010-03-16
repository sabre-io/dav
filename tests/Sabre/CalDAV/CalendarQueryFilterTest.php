<?php

class Sabre_CalDAV_CalendarQueryFilterTest extends PHPUnit_Framework_TestCase {

    protected function getTestCalendarData($type = 0) {

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
TRANSP:TRANSPARENT
SUMMARY:Something here
DTSTART;TZID=Asia/Seoul:20100223T060000
DTSTAMP:20100228T130202Z';

        switch($type) {
            case 0 :
                $calendarData.="\nDTEND;TZID=Asia/Seoul:20100223T070000\n";
                break;
            case 1 :
                $calendarData.="\nDTEND:20100223T070000\n";
                break;
            case 2 :
                $calendarData.="\nDURATION:PT1H\n";
                break;
            case 3 :
                $calendarData.="\nDURATION:PT0S\n";
                break;
        }


        $calendarData.='ATTENDEE;PARTSTAT=NEEDS-ACTION:mailto:lisa@example.com
SEQUENCE:2
END:VEVENT
END:VCALENDAR';

        return $calendarData;

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

        $this->markTestIncomplete('not yet ready'); 

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
        $this->assertTrue($calendarPlugin->validateFilters($this->getTestCalendarData(0),$filters));
        $this->assertTrue($calendarPlugin->validateFilters($this->getTestCalendarData(1),$filters));
        $this->assertTrue($calendarPlugin->validateFilters($this->getTestCalendarData(2),$filters));
        $this->assertTrue($calendarPlugin->validateFilters($this->getTestCalendarData(3),$filters));

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
