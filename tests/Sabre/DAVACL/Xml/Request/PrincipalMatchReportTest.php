<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Request;

class PrincipalMatchReportTest extends \Sabre\DAV\Xml\XmlTest
{
    protected $elementMap = [
        '{DAV:}principal-match' => 'Sabre\DAVACL\Xml\Request\PrincipalMatchReport',
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

        $this->assertEquals(PrincipalMatchReport::PRINCIPAL_PROPERTY, $result['value']->type);
        $this->assertEquals('{DAV:}owner', $result['value']->principalProperty);
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

        $this->assertEquals(PrincipalMatchReport::SELF, $result['value']->type);
        $this->assertNull($result['value']->principalProperty);
        $this->assertEquals(['{DAV:}foo'], $result['value']->properties);
    }
}
