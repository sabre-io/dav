<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class SupportedCalendarComponentSetTest extends DAV\Xml\AbstractXmlTestCase
{
    public function setup(): void
    {
        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $this->namespaceMap[CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';
    }

    public function testSimple()
    {
        $prop = new SupportedCalendarComponentSet(['VEVENT']);
        self::assertEquals(
            ['VEVENT'],
            $prop->getValue()
        );
    }

    public function testMultiple()
    {
        $prop = new SupportedCalendarComponentSet(['VEVENT', 'VTODO']);
        self::assertEquals(
            ['VEVENT', 'VTODO'],
            $prop->getValue()
        );
    }

    /**
     * @depends testSimple
     * @depends testMultiple
     */
    public function testSerialize()
    {
        $property = new SupportedCalendarComponentSet(['VEVENT', 'VTODO']);
        $xml = $this->write(['{DAV:}root' => $property]);

        self::assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="'.CalDAV\Plugin::NS_CALDAV.'" xmlns:cs="'.CalDAV\Plugin::NS_CALENDARSERVER.'">
  <cal:comp name="VEVENT"/>
  <cal:comp name="VTODO"/>
</d:root>
', $xml);
    }

    public function testUnserialize()
    {
        $cal = CalDAV\Plugin::NS_CALDAV;
        $cs = CalDAV\Plugin::NS_CALENDARSERVER;

        $xml = <<<XML
<?xml version="1.0"?>
 <d:root xmlns:cal="$cal" xmlns:cs="$cs" xmlns:d="DAV:">
   <cal:comp name="VEVENT"/>
   <cal:comp name="VTODO"/>
 </d:root>
XML;

        $result = $this->parse(
            $xml,
            ['{DAV:}root' => \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet::class]
        );

        self::assertEquals(
            new SupportedCalendarComponentSet(['VEVENT', 'VTODO']),
            $result['value']
        );
    }

    public function testUnserializeEmpty()
    {
        $this->expectException(\Sabre\Xml\ParseException::class);
        $cal = CalDAV\Plugin::NS_CALDAV;
        $cs = CalDAV\Plugin::NS_CALENDARSERVER;

        $xml = <<<XML
<?xml version="1.0"?>
 <d:root xmlns:cal="$cal" xmlns:cs="$cs" xmlns:d="DAV:">
 </d:root>
XML;

        $result = $this->parse(
            $xml,
            ['{DAV:}root' => \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet::class]
        );
    }
}
