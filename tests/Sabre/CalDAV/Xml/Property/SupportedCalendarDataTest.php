<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class SupportedCalendarDataTest extends DAV\Xml\XmlTest
{
    public function testSimple()
    {
        $sccs = new SupportedCalendarData();
        self::assertInstanceOf('Sabre\CalDAV\Xml\Property\SupportedCalendarData', $sccs);
    }

    /**
     * @depends testSimple
     */
    public function testSerialize()
    {
        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $property = new SupportedCalendarData();

        $xml = $this->write(['{DAV:}root' => $property]);

        self::assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="'.CalDAV\Plugin::NS_CALDAV.'">
<cal:calendar-data content-type="text/calendar" version="2.0"/>
<cal:calendar-data content-type="application/calendar+json"/>
</d:root>', $xml);
    }
}
