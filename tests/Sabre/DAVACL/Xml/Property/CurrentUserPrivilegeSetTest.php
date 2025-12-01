<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Reader;

class CurrentUserPrivilegeSetTest extends \PHPUnit\Framework\TestCase
{
    public function testSerialize()
    {
        $privileges = [
            '{DAV:}read',
            '{DAV:}write',
        ];
        $prop = new CurrentUserPrivilegeSet($privileges);
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $prop);

        $expected = <<<XML
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
    <d:privilege>
        <d:read />
    </d:privilege>
    <d:privilege>
        <d:write />
    </d:privilege>
</d:root>
XML;

        self::assertXmlStringEqualsXmlString($expected, $xml);
    }

    public function testUnserialize()
    {
        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
    <d:privilege>
        <d:write-properties />
    </d:privilege>
    <d:ignoreme />
    <d:privilege>
        <d:read />
    </d:privilege>
</d:root>
';

        $result = $this->parse($source);
        self::assertTrue($result->has('{DAV:}read'));
        self::assertTrue($result->has('{DAV:}write-properties'));
        self::assertFalse($result->has('{DAV:}bind'));
    }

    public function parse($xml)
    {
        $reader = new Reader();
        $reader->elementMap['{DAV:}root'] = CurrentUserPrivilegeSet::class;
        $reader->xml($xml);
        $result = $reader->parse();

        return $result['value'];
    }

    public function testToHtml()
    {
        $privileges = ['{DAV:}read', '{DAV:}write'];

        $prop = new CurrentUserPrivilegeSet($privileges);
        $html = new HtmlOutputHelper(
            '/base/',
            ['DAV:' => 'd']
        );

        $expected =
            '<span title="{DAV:}read">d:read</span>, '.
            '<span title="{DAV:}write">d:write</span>';

        self::assertEquals($expected, $prop->toHtml($html));
    }
}
