<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Xml\AbstractXmlTestCase;

class PropFindTest extends AbstractXmlTestCase
{
    public function testDeserializeProp()
    {
        $xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
    <d:prop>
        <d:hello />
    </d:prop>
</d:root>
';

        $result = $this->parse($xml, ['{DAV:}root' => \Sabre\DAV\Xml\Request\PropFind::class]);

        $propFind = new PropFind();
        $propFind->properties = ['{DAV:}hello'];

        self::assertEquals($propFind, $result['value']);
    }

    public function testDeserializeAllProp()
    {
        $xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
    <d:allprop />
</d:root>
';

        $result = $this->parse($xml, ['{DAV:}root' => \Sabre\DAV\Xml\Request\PropFind::class]);

        $propFind = new PropFind();
        $propFind->allProp = true;

        self::assertEquals($propFind, $result['value']);
    }
}
