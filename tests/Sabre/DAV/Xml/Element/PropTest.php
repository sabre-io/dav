<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Element;

use Sabre\DAV\Xml\Property\Complex;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAV\Xml\XmlTest;

class PropTest extends XmlTest
{
    public function testDeserializeSimple()
    {
        $input = <<<XML
<?xml version="1.0"?>
<root xmlns="DAV:">
    <foo>bar</foo>
</root>
XML;

        $expected = [
            '{DAV:}foo' => 'bar',
        ];

        $this->assertDecodeProp($input, $expected);
    }

    public function testDeserializeEmpty()
    {
        $input = <<<XML
<?xml version="1.0"?>
<root xmlns="DAV:" />
XML;

        $expected = [
        ];

        $this->assertDecodeProp($input, $expected);
    }

    public function testDeserializeComplex()
    {
        $input = <<<XML
<?xml version="1.0"?>
<root xmlns="DAV:">
    <foo><no>yes</no></foo>
</root>
XML;

        $expected = [
            '{DAV:}foo' => new Complex('<no xmlns="DAV:">yes</no>'),
        ];

        $this->assertDecodeProp($input, $expected);
    }

    public function testDeserializeCustom()
    {
        $input = <<<XML
<?xml version="1.0"?>
<root xmlns="DAV:">
    <foo><href>/hello</href></foo>
</root>
XML;

        $expected = [
            '{DAV:}foo' => new Href('/hello'),
        ];

        $elementMap = [
            '{DAV:}foo' => 'Sabre\DAV\Xml\Property\Href',
        ];

        $this->assertDecodeProp($input, $expected, $elementMap);
    }

    public function testDeserializeCustomCallback()
    {
        $input = <<<XML
<?xml version="1.0"?>
<root xmlns="DAV:">
    <foo>blabla</foo>
</root>
XML;

        $expected = [
            '{DAV:}foo' => 'zim',
        ];

        $elementMap = [
            '{DAV:}foo' => function ($reader) {
                $reader->next();

                return 'zim';
            },
        ];

        $this->assertDecodeProp($input, $expected, $elementMap);
    }

    public function testDeserializeCustomBad()
    {
        $this->expectException('LogicException');
        $input = <<<XML
<?xml version="1.0"?>
<root xmlns="DAV:">
    <foo>blabla</foo>
</root>
XML;

        $expected = [];

        $elementMap = [
            '{DAV:}foo' => 'idk?',
        ];

        $this->assertDecodeProp($input, $expected, $elementMap);
    }

    public function testDeserializeCustomBadObj()
    {
        $this->expectException('LogicException');
        $input = <<<XML
<?xml version="1.0"?>
<root xmlns="DAV:">
    <foo>blabla</foo>
</root>
XML;

        $expected = [];

        $elementMap = [
            '{DAV:}foo' => new \stdClass(),
        ];

        $this->assertDecodeProp($input, $expected, $elementMap);
    }

    public function assertDecodeProp($input, array $expected, array $elementMap = [])
    {
        $elementMap['{DAV:}root'] = 'Sabre\DAV\Xml\Element\Prop';

        $result = $this->parse($input, $elementMap);
        $this->assertIsArray($result);
        $this->assertEquals($expected, $result['value']);
    }
}
