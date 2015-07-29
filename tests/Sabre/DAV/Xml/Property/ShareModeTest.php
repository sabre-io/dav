<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Xml\XmlTest;

class ShareModeTest extends XmlTest {

    function testSerialize() {

        $data = ['{DAV:}root' => [
            [
                'name'  => '{DAV:}share-mode',
                'value' => new ShareMode(ShareMode::SHARED),
            ],
            [
                'name'  => '{DAV:}share-mode',
                'value' => new ShareMode(ShareMode::SHAREDOWNER),
            ],
        ]];

        $xml = $this->write($data);

        $expected = <<<XML
<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:share-mode><d:shared /></d:share-mode>
  <d:share-mode><d:shared-owner /></d:share-mode>
</d:root>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $xml);

    }

}
