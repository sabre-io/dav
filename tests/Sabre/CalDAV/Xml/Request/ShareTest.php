<?php

namespace Sabre\CalDAV\Xml\Request;

use Sabre\DAV\Xml\XmlTest;

class ShareTest extends XmlTest {

    protected $elementMap = [
        '{http://calendarserver.org/ns/}share' => 'Sabre\\CalDAV\\Xml\\Request\\Share',
    ];

    function testDeserialize() {

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
   <CS:share xmlns:D="DAV:"
                 xmlns:CS="http://calendarserver.org/ns/">
     <CS:set>
       <D:href>mailto:eric@example.com</D:href>
       <CS:common-name>Eric York</CS:common-name>
       <CS:summary>Shared workspace</CS:summary>
       <CS:read-write />
     </CS:set>
     <CS:remove>
       <D:href>mailto:foo@bar</D:href>
     </CS:remove>
   </CS:share>
XML;

        $result = $this->parse($xml);
        $share = new Share(
            [
                [
                    'href'       => 'mailto:eric@example.com',
                    'commonName' => 'Eric York',
                    'summary'    => 'Shared workspace',
                    'readOnly'   => false,
                ]
            ],
            [
                'mailto:foo@bar',
            ]
        );

        $this->assertEquals(
            $share,
            $result['value']
        );

    }

    function testDeserializeMininal() {

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
   <CS:share xmlns:D="DAV:"
                 xmlns:CS="http://calendarserver.org/ns/">
     <CS:set>
       <D:href>mailto:eric@example.com</D:href>
        <CS:read />
     </CS:set>
   </CS:share>
XML;

        $result = $this->parse($xml);
        $share = new Share(
            [
                [
                    'href'       => 'mailto:eric@example.com',
                    'commonName' => null,
                    'summary'    => null,
                    'readOnly'   => true,
                ]
            ],
            []
        );

        $this->assertEquals(
            $share,
            $result['value']
        );

    }

}
