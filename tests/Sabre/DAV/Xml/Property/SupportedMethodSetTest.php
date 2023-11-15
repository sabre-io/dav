<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Xml\AbstractXmlTestCase;

class SupportedMethodSetTest extends AbstractXmlTestCase
{
    public function testSimple()
    {
        $cus = new SupportedMethodSet(['GET', 'PUT']);
        self::assertEquals(['GET', 'PUT'], $cus->getValue());

        self::assertTrue($cus->has('GET'));
        self::assertFalse($cus->has('HEAD'));
    }

    public function testSerialize()
    {
        $cus = new SupportedMethodSet(['GET', 'PUT']);
        $xml = $this->write(['{DAV:}foo' => $cus]);

        $expected = '<?xml version="1.0"?>
<d:foo xmlns:d="DAV:">
    <d:supported-method name="GET"/>
    <d:supported-method name="PUT"/>
</d:foo>';

        self::assertXmlStringEqualsXmlString($expected, $xml);
    }

    public function testSerializeHtml()
    {
        $cus = new SupportedMethodSet(['GET', 'PUT']);
        $result = $cus->toHtml(
            new \Sabre\DAV\Browser\HtmlOutputHelper('/', [])
        );

        self::assertEquals('GET, PUT', $result);
    }
}
