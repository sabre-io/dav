<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\DAV\Xml\AbstractXmlTestCase;

class HrefTest extends AbstractXmlTestCase
{
    public function testConstruct()
    {
        $href = new Href('path');
        self::assertEquals('path', $href->getHref());
    }

    public function testSerialize()
    {
        $href = new Href('path');
        self::assertEquals('path', $href->getHref());

        $this->contextUri = '/bla/';

        $xml = $this->write(['{DAV:}anything' => $href]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>/bla/path</d:href></d:anything>
', $xml);
    }

    public function testUnserialize()
    {
        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>/bla/path</d:href></d:anything>
';

        $result = $this->parse($xml, ['{DAV:}anything' => Href::class]);

        $href = $result['value'];

        self::assertInstanceOf(Href::class, $href);

        self::assertEquals('/bla/path', $href->getHref());
    }

    public function testUnserializeIncompatible()
    {
        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href2>/bla/path</d:href2></d:anything>
';
        $result = $this->parse($xml, ['{DAV:}anything' => Href::class]);
        $href = $result['value'];
        self::assertNull($href);
    }

    public function testUnserializeEmpty()
    {
        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"></d:anything>
';
        $result = $this->parse($xml, ['{DAV:}anything' => Href::class]);
        $href = $result['value'];
        self::assertNull($href);
    }

    /**
     * This method tests if hrefs containing & are correctly encoded.
     */
    public function testSerializeEntity()
    {
        $href = new Href('http://example.org/?a&b');
        self::assertEquals('http://example.org/?a&b', $href->getHref());

        $xml = $this->write(['{DAV:}anything' => $href]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>http://example.org/?a&amp;b</d:href></d:anything>
', $xml);
    }

    public function testToHtml()
    {
        $href = new Href([
            '/foo/bar',
            'foo/bar',
            'http://example.org/bar',
        ]);

        $html = new HtmlOutputHelper(
            '/base/',
            []
        );

        $expected =
            '<a href="/foo/bar">/foo/bar</a><br />'.
            '<a href="/base/foo/bar">/base/foo/bar</a><br />'.
            '<a href="http://example.org/bar">http://example.org/bar</a>';
        self::assertEquals($expected, $href->toHtml($html));
    }
}
