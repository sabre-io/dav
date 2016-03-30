<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Xml\XmlTest;
use Sabre\DAV\Sharing\Plugin;

class ShareAccessTest extends XmlTest {

    function testSerialize() {

        $data = ['{DAV:}root' => [
            [
                'name'  => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_READ),
            ],
            [
                'name'  => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_READWRITE),
            ],
            [
                'name'  => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_NOTSHARED),
            ],
            [
                'name'  => '{DAV:}share-access',
                'value' => new ShareAccess(Plugin::ACCESS_NOACCESS),
            ],
            [
                'name'  => '{DAV:}share-access',
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

        $this->assertXmlStringEqualsXmlString($expected, $xml);

    }

}
