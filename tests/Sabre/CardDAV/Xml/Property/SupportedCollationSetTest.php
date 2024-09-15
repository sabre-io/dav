<?php

declare(strict_types=1);

namespace Sabre\CardDAV\Xml\Property;

use Sabre\CardDAV;
use Sabre\DAV;

class SupportedCollationSetTest extends DAV\Xml\XmlTest
{
    public function testSimple()
    {
        $property = new SupportedCollationSet();
        self::assertInstanceOf(\Sabre\CardDAV\Xml\Property\SupportedCollationSet::class, $property);
    }

    /**
     * @depends testSimple
     */
    public function testSerialize()
    {
        $property = new SupportedCollationSet();

        $this->namespaceMap[CardDAV\Plugin::NS_CARDDAV] = 'card';
        $xml = $this->write(['{DAV:}root' => $property]);

        self::assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:card="'.CardDAV\Plugin::NS_CARDDAV.'" xmlns:d="DAV:">'.
'<card:supported-collation>i;ascii-casemap</card:supported-collation>'.
'<card:supported-collation>i;octet</card:supported-collation>'.
'<card:supported-collation>i;unicode-casemap</card:supported-collation>'.
'</d:root>
', $xml);
    }
}
