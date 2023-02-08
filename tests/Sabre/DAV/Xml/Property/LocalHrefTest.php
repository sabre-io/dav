<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\DAV\Xml\XmlTest;

class LocalHrefTest extends XmlTest
{
    public function testConstruct()
    {
        $href = new LocalHref('path');
        self::assertEquals('path', $href->getHref());
    }

    public function testSerialize()
    {
        $href = new LocalHref('path');
        self::assertEquals('path', $href->getHref());

        $this->contextUri = '/bla/';

        $xml = $this->write(['{DAV:}anything' => $href]);

        self::assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>/bla/path</d:href></d:anything>
', $xml);
    }

    public function testSerializeSpace()
    {
        $href = new LocalHref('path alsopath');
        self::assertEquals('path%20alsopath', $href->getHref());

        $this->contextUri = '/bla/';

        $xml = $this->write(['{DAV:}anything' => $href]);

        self::assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>/bla/path%20alsopath</d:href></d:anything>
', $xml);
    }

    public function testToHtml()
    {
        $href = new LocalHref([
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
