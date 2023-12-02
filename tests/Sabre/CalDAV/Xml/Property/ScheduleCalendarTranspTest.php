<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class ScheduleCalendarTranspTest extends DAV\Xml\AbstractXmlTestCase
{
    public function setup(): void
    {
        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $this->namespaceMap[CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';
    }

    public function testSimple()
    {
        $prop = new ScheduleCalendarTransp(ScheduleCalendarTransp::OPAQUE);
        self::assertEquals(
            ScheduleCalendarTransp::OPAQUE,
            $prop->getValue()
        );
    }

    public function testBadValue()
    {
        $this->expectException('InvalidArgumentException');
        new ScheduleCalendarTransp('ahhh');
    }

    /**
     * @depends testSimple
     */
    public function testSerializeOpaque()
    {
        $property = new ScheduleCalendarTransp(ScheduleCalendarTransp::OPAQUE);
        $xml = $this->write(['{DAV:}root' => $property]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="'.CalDAV\Plugin::NS_CALDAV.'" xmlns:cs="'.CalDAV\Plugin::NS_CALENDARSERVER.'">
  <cal:opaque />
</d:root>
', $xml);
    }

    /**
     * @depends testSimple
     */
    public function testSerializeTransparent()
    {
        $property = new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT);
        $xml = $this->write(['{DAV:}root' => $property]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="'.CalDAV\Plugin::NS_CALDAV.'" xmlns:cs="'.CalDAV\Plugin::NS_CALENDARSERVER.'">
  <cal:transparent />
</d:root>
', $xml);
    }

    public function testUnserializeTransparent()
    {
        $cal = CalDAV\Plugin::NS_CALDAV;
        $cs = CalDAV\Plugin::NS_CALENDARSERVER;

        $xml = <<<XML
<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="$cal" xmlns:cs="$cs">
  <cal:transparent />
</d:root>
XML;

        $result = $this->parse(
            $xml,
            ['{DAV:}root' => \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp::class]
        );

        self::assertEquals(
            new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT),
            $result['value']
        );
    }

    public function testUnserializeOpaque()
    {
        $cal = CalDAV\Plugin::NS_CALDAV;
        $cs = CalDAV\Plugin::NS_CALENDARSERVER;

        $xml = <<<XML
<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="$cal" xmlns:cs="$cs">
  <cal:opaque />
</d:root>
XML;

        $result = $this->parse(
            $xml,
            ['{DAV:}root' => \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp::class]
        );

        self::assertEquals(
            new ScheduleCalendarTransp(ScheduleCalendarTransp::OPAQUE),
            $result['value']
        );
    }
}
