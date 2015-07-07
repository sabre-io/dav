<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Xml\XmlTest;

class ShareResourceTest extends XmlTest {

    function testDeserialize() {

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:share-resource xmlns:D="DAV:">
   <D:set-invitee>
     <D:href>mailto:eric@example.com</D:href>
     <D:displayname>Eric York</D:displayname>
     <D:comment>Shared workspace</D:comment>
     <D:read-write />
   </D:set-invitee>
   <D:set-invitee>
     <D:href>mailto:evert@example.com</D:href>
     <D:displayname>Evert Pot</D:displayname>
     <D:comment>Shared workspace</D:comment>
     <D:read />
   </D:set-invitee>
   <D:remove-invitee>
     <D:href>mailto:wilfredo@example.com</D:href>
   </D:remove-invitee>
</D:share-resource>
XML;

        $result = $this->parse($xml, [
            '{DAV:}share-resource' => 'Sabre\\DAV\\Xml\\Request\\ShareResource'
        ]);

        $this->assertInstanceOf(
            'Sabre\\DAV\\Xml\\Request\\ShareResource',
            $result['value']
        );

        $this->assertEquals(
            [
                [
                    'href' => 'mailto:eric@example.com',
                    '{DAV:}displayname' => 'Eric York',
                    'comment' => 'Shared workspace',
                    'readOnly' => false,
                ],
                [
                    'href' => 'mailto:evert@example.com',
                    '{DAV:}displayname' => 'Evert Pot',
                    'comment' => 'Shared workspace',
                    'readOnly' => true,
                ],
            ],
            $result['value']->set
        );

        $this->assertEquals(
            [
                'mailto:wilfredo@example.com',
            ],
            $result['value']->remove
        );

    }

}
