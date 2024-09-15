<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class SupportedCollationSetTest extends DAV\Xml\XmlTest
{
    public function testSimple()
    {
        $scs = new SupportedCollationSet();
        self::assertInstanceOf(\Sabre\CalDAV\Xml\Property\SupportedCollationSet::class, $scs);
    }

    /**
     * @depends testSimple
     */
    public function testSerialize()
    {
        $property = new SupportedCollationSet();

        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $xml = $this->write(['{DAV:}root' => $property]);

        self::assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="'.CalDAV\Plugin::NS_CALDAV.'">
<cal:supported-collation>i;ascii-casemap</cal:supported-collation>
<cal:supported-collation>i;octet</cal:supported-collation>
<cal:supported-collation>i;unicode-casemap</cal:supported-collation>
</d:root>', $xml);
    }
}
