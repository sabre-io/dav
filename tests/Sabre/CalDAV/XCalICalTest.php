<?php

class Sabre_CalDAV_XCalICalTest extends PHPUnit_Framework_TestCase {

    function testSimple() {

        $in = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'END:VCALENDAR');

        $out = Sabre_CalDAV_XCalICal::toXCal(implode("\n",$in));
        
        $compare = '<?xml version="1.0"?>
<iCalendar xmlns="urn:ietf:params:xml:ns:xcal">
  <vcalendar>
    <version>2.0</version>
    <prodid>-//hacksw/handcal//NONSGML v1.0//EN</prodid>
  </vcalendar>
</iCalendar>';

        $this->assertEquals($compare, $out);

    }

    function testWindowsLineEndings() {

        $in = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'END:VCALENDAR');

        $out = Sabre_CalDAV_XCalICal::toXCal(implode("\r\n",$in));
        
        $compare = '<?xml version="1.0"?>
<iCalendar xmlns="urn:ietf:params:xml:ns:xcal">
  <vcalendar>
    <version>2.0</version>
    <prodid>-//hacksw/handcal//NONSGML v1.0//EN</prodid>
  </vcalendar>
</iCalendar>';

        $this->assertEquals($compare, $out);


    }

    function testEvent() {

        $in = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'BEGIN:VEVENT',
            'SUMMARY:meeting',
            'END:VEVENT',
            'END:VCALENDAR');

        $out = Sabre_CalDAV_XCalICal::toXCal(implode("\n",$in));
        
        $compare = '<?xml version="1.0"?>
<iCalendar xmlns="urn:ietf:params:xml:ns:xcal">
  <vcalendar>
    <version>2.0</version>
    <prodid>-//hacksw/handcal//NONSGML v1.0//EN</prodid>
    <vevent>
      <summary>meeting</summary>
    </vevent>
  </vcalendar>
</iCalendar>';

        $this->assertEquals($compare, $out);

    }

    function testLineFolding() {

        $in = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'BEGIN:VEVENT',
            'SUMMARY:mee',
            ' ting',
            'END:VEVENT',
            'END:VCALENDAR');

        $out = Sabre_CalDAV_XCalICal::toXCal(implode("\n",$in));
        
        $compare = '<?xml version="1.0"?>
<iCalendar xmlns="urn:ietf:params:xml:ns:xcal">
  <vcalendar>
    <version>2.0</version>
    <prodid>-//hacksw/handcal//NONSGML v1.0//EN</prodid>
    <vevent>
      <summary>meeting</summary>
    </vevent>
  </vcalendar>
</iCalendar>';

        $this->assertEquals($compare, $out);

    }

    function testAttribute() {

        $in = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'BEGIN:VEVENT',
            'SUMMARY:meeting',
            'X-SABRE;att1=val1:This is the property content',
            'END:VEVENT',
            'END:VCALENDAR');

        $out = Sabre_CalDAV_XCalICal::toXCal(implode("\n",$in));
        
        $compare = '<?xml version="1.0"?>
<iCalendar xmlns="urn:ietf:params:xml:ns:xcal">
  <vcalendar>
    <version>2.0</version>
    <prodid>-//hacksw/handcal//NONSGML v1.0//EN</prodid>
    <vevent>
      <summary>meeting</summary>
      <x-sabre att1="val1">This is the property content</x-sabre>
    </vevent>
  </vcalendar>
</iCalendar>';

        $this->assertEquals($compare, $out);

    }

    function testAttribute2() {

        $in = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'BEGIN:VEVENT',
            'SUMMARY;LANGUAGE=nl-NL:meeting',
            'X-SABRE;att1=val1;att2=val2:This is the property content',
            'END:VEVENT',
            'END:VCALENDAR');

        $out = Sabre_CalDAV_XCalICal::toXCal(implode("\n",$in));
        
        $compare = '<?xml version="1.0"?>
<iCalendar xmlns="urn:ietf:params:xml:ns:xcal">
  <vcalendar>
    <version>2.0</version>
    <prodid>-//hacksw/handcal//NONSGML v1.0//EN</prodid>
    <vevent>
      <summary xml:lang="nl-NL">meeting</summary>
      <x-sabre att1="val1" att2="val2">This is the property content</x-sabre>
    </vevent>
  </vcalendar>
</iCalendar>';

        $this->assertEquals($compare, $out);

    }
}
