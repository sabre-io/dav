<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Element;

use Sabre\DAV\Xml\AbstractXmlTestCase;
use Sabre\DAV\Xml\Property\Complex;
use Sabre\DAV\Xml\Property\Href;

class PropTest extends AbstractXmlTestCase
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

        self::assertDecodeProp($input, $expected);
    }

    public function testDeserializeEmpty()
    {
        $input = <<<XML
<?xml version="1.0"?>
<root xmlns="DAV:" />
XML;

        $expected = [
        ];

        self::assertDecodeProp($input, $expected);
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

        self::assertDecodeProp($input, $expected);
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

        self::assertDecodeProp($input, $expected, $elementMap);
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

        self::assertDecodeProp($input, $expected, $elementMap);
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

        self::assertDecodeProp($input, $expected, $elementMap);
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
            '{DAV:}foo' => new \StdClass(),
        ];

        self::assertDecodeProp($input, $expected, $elementMap);
    }

    public function assertDecodeProp($input, array $expected, array $elementMap = [])
    {
        $elementMap['{DAV:}root'] = 'Sabre\DAV\Xml\Element\Prop';

        $result = $this->parse($input, $elementMap);
        self::assertIsArray($result);
        self::assertEquals($expected, $result['value']);
    }
}
