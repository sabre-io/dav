<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Request;

class AclPrincipalPropSetReportTest extends \Sabre\DAV\Xml\AbstractXmlTestCase
{
    protected $elementMap = [
        '{DAV:}acl-principal-prop-set' => AclPrincipalPropSetReport::class,
    ];

    public function testDeserialize()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:acl-principal-prop-set xmlns:D="DAV:">
 <D:prop>
   <D:displayname/>
 </D:prop>
</D:acl-principal-prop-set>
XML;

        $result = $this->parse($xml);

        self::assertEquals(['{DAV:}displayname'], $result['value']->properties);
    }
}
