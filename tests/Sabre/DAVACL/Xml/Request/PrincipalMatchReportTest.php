<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Request;

class PrincipalMatchReportTest extends \Sabre\DAV\Xml\XmlTest
{
    protected $elementMap = [
        '{DAV:}principal-match' => \Sabre\DAVACL\Xml\Request\PrincipalMatchReport::class,
    ];

    public function testDeserialize()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
   <D:principal-match xmlns:D="DAV:">
     <D:principal-property>
       <D:owner/>
     </D:principal-property>
   </D:principal-match>
XML;

        $result = $this->parse($xml);

        self::assertEquals(PrincipalMatchReport::PRINCIPAL_PROPERTY, $result['value']->type);
        self::assertEquals('{DAV:}owner', $result['value']->principalProperty);
    }

    public function testDeserializeSelf()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
   <D:principal-match xmlns:D="DAV:">
     <D:self />
     <D:prop>
        <D:foo />
     </D:prop>
   </D:principal-match>
XML;

        $result = $this->parse($xml);

        self::assertEquals(PrincipalMatchReport::SELF, $result['value']->type);
        self::assertNull($result['value']->principalProperty);
        self::assertEquals(['{DAV:}foo'], $result['value']->properties);
    }
}
