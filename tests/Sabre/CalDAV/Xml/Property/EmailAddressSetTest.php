<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\DAV\Xml\XmlTest;

class EmailAddressSetTest extends XmlTest
{
    protected $namespaceMap = [
        \Sabre\CalDAV\Plugin::NS_CALENDARSERVER => 'cs',
        'DAV:' => 'd',
    ];

    public function testSimple()
    {
        $eas = new EmailAddressSet(['foo@example.org']);
        $this->assertEquals(['foo@example.org'], $eas->getValue());
    }

    /**
     * @depends testSimple
     */
    public function testSerialize()
    {
        $property = new EmailAddressSet(['foo@example.org']);

        $xml = $this->write([
            '{DAV:}root' => $property,
        ]);

        $this->assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cs="'.\Sabre\CalDAV\Plugin::NS_CALENDARSERVER.'">
<cs:email-address>foo@example.org</cs:email-address>
</d:root>', $xml);
    }
}
