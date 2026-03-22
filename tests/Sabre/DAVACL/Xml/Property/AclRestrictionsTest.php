<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;

class AclRestrictionsTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $prop = new AclRestrictions();
        self::assertInstanceOf(\Sabre\DAVACL\Xml\Property\AclRestrictions::class, $prop);
    }

    public function testSerialize()
    {
        $prop = new AclRestrictions();
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $prop);

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns"><d:grant-only/><d:no-invert/></d:root>';

        self::assertXmlStringEqualsXmlString($expected, $xml);
    }
}
