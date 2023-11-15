<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class AllowedSharingModesTest extends DAV\Xml\AbstractXmlTestCase
{
    public function testSimple()
    {
        $sccs = new AllowedSharingModes(true, true);
        self::assertInstanceOf('Sabre\CalDAV\Xml\Property\AllowedSharingModes', $sccs);
    }

    /**
     * @depends testSimple
     */
    public function testSerialize()
    {
        $property = new AllowedSharingModes(true, true);

        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $this->namespaceMap[CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';
        $xml = $this->write(['{DAV:}root' => $property]);

        self::assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
  <d:root xmlns:d="DAV:" xmlns:cal="'.CalDAV\Plugin::NS_CALDAV.'" xmlns:cs="'.CalDAV\Plugin::NS_CALENDARSERVER.'">
    <cs:can-be-shared/>
    <cs:can-be-published/>
</d:root>
', $xml);
    }
}
