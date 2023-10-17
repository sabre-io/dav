<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Sharing\Plugin;
use Sabre\DAV\Xml\XmlTest;

class ShareAccessTest extends XmlTest
{
    public function testSerialize()
    {
        $data = ['{DAV:}root' => [
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_READ),
            ],
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_READWRITE),
            ],
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_NOTSHARED),
            ],
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_NOACCESS),
            ],
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_SHAREDOWNER),
            ],
        ]];

        $xml = $this->write($data);

        $expected = <<<XML
<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:share-access><d:read /></d:share-access>
  <d:share-access><d:read-write /></d:share-access>
  <d:share-access><d:not-shared /></d:share-access>
  <d:share-access><d:no-access /></d:share-access>
  <d:share-access><d:shared-owner /></d:share-access>
</d:root>
XML;

        self::assertXmlStringEqualsXmlString($expected, $xml);
    }

    public function testDeserialize()
    {
        $input = <<<XML
<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:share-access><d:read /></d:share-access>
  <d:share-access><d:read-write /></d:share-access>
  <d:share-access><d:not-shared /></d:share-access>
  <d:share-access><d:no-access /></d:share-access>
  <d:share-access><d:shared-owner /></d:share-access>
</d:root>
XML;

        $data = [
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_READ),
                'attributes' => [],
            ],
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_READWRITE),
                'attributes' => [],
            ],
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_NOTSHARED),
                'attributes' => [],
            ],
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_NOACCESS),
                'attributes' => [],
            ],
            [
                'name' => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_SHAREDOWNER),
                'attributes' => [],
            ],
        ];

        self::assertParsedValue(
            $data,
            $input,
            ['{DAV:}share-access' => ShareAccess::class]
        );
    }

    public function testDeserializeInvalid()
    {
        $this->expectException('Sabre\DAV\Exception\BadRequest');
        $input = <<<XML
<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:share-access><d:foo /></d:share-access>
</d:root>
XML;

        $this->parse(
            $input,
            ['{DAV:}share-access' => ShareAccess::class]
        );
    }
}
