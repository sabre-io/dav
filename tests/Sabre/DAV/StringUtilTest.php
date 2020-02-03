<?php

declare(strict_types=1);

namespace Sabre\DAV;

class StringUtilTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $haystack
     * @param string $needle
     * @param string $collation
     * @param string $matchType
     * @param string $result
     *
     * @throws Exception\BadRequest
     *
     * @dataProvider dataset
     */
    public function testTextMatch($haystack, $needle, $collation, $matchType, $result)
    {
        $this->assertEquals($result, StringUtil::textMatch($haystack, $needle, $collation, $matchType));
    }

    public function dataset()
    {
        return [
            ['FOOBAR', 'FOO',    'i;octet', 'contains', true],
            ['FOOBAR', 'foo',    'i;octet', 'contains', false],
            ['FÖÖBAR', 'FÖÖ',    'i;octet', 'contains', true],
            ['FÖÖBAR', 'föö',    'i;octet', 'contains', false],
            ['FOOBAR', 'FOOBAR', 'i;octet', 'equals', true],
            ['FOOBAR', 'fooBAR', 'i;octet', 'equals', false],
            ['FOOBAR', 'FOO',    'i;octet', 'starts-with', true],
            ['FOOBAR', 'foo',    'i;octet', 'starts-with', false],
            ['FOOBAR', 'BAR',    'i;octet', 'starts-with', false],
            ['FOOBAR', 'bar',    'i;octet', 'starts-with', false],
            ['FOOBAR', 'FOO',    'i;octet', 'ends-with', false],
            ['FOOBAR', 'foo',    'i;octet', 'ends-with', false],
            ['FOOBAR', 'BAR',    'i;octet', 'ends-with', true],
            ['FOOBAR', 'bar',    'i;octet', 'ends-with', false],

            ['FOOBAR', 'FOO',    'i;ascii-casemap', 'contains', true],
            ['FOOBAR', 'foo',    'i;ascii-casemap', 'contains', true],
            ['FÖÖBAR', 'FÖÖ',    'i;ascii-casemap', 'contains', true],
            ['FÖÖBAR', 'föö',    'i;ascii-casemap', 'contains', false],
            ['FOOBAR', 'FOOBAR', 'i;ascii-casemap', 'equals', true],
            ['FOOBAR', 'fooBAR', 'i;ascii-casemap', 'equals', true],
            ['FOOBAR', 'FOO',    'i;ascii-casemap', 'starts-with', true],
            ['FOOBAR', 'foo',    'i;ascii-casemap', 'starts-with', true],
            ['FOOBAR', 'BAR',    'i;ascii-casemap', 'starts-with', false],
            ['FOOBAR', 'bar',    'i;ascii-casemap', 'starts-with', false],
            ['FOOBAR', 'FOO',    'i;ascii-casemap', 'ends-with', false],
            ['FOOBAR', 'foo',    'i;ascii-casemap', 'ends-with', false],
            ['FOOBAR', 'BAR',    'i;ascii-casemap', 'ends-with', true],
            ['FOOBAR', 'bar',    'i;ascii-casemap', 'ends-with', true],

            ['FOOBAR', 'FOO',    'i;unicode-casemap', 'contains', true],
            ['FOOBAR', 'foo',    'i;unicode-casemap', 'contains', true],
            ['FÖÖBAR', 'FÖÖ',    'i;unicode-casemap', 'contains', true],
            ['FÖÖBAR', 'föö',    'i;unicode-casemap', 'contains', true],
            ['FOOBAR', 'FOOBAR', 'i;unicode-casemap', 'equals', true],
            ['FOOBAR', 'fooBAR', 'i;unicode-casemap', 'equals', true],
            ['FOOBAR', 'FOO',    'i;unicode-casemap', 'starts-with', true],
            ['FOOBAR', 'foo',    'i;unicode-casemap', 'starts-with', true],
            ['FOOBAR', 'BAR',    'i;unicode-casemap', 'starts-with', false],
            ['FOOBAR', 'bar',    'i;unicode-casemap', 'starts-with', false],
            ['FOOBAR', 'FOO',    'i;unicode-casemap', 'ends-with', false],
            ['FOOBAR', 'foo',    'i;unicode-casemap', 'ends-with', false],
            ['FOOBAR', 'BAR',    'i;unicode-casemap', 'ends-with', true],
            ['FOOBAR', 'bar',    'i;unicode-casemap', 'ends-with', true],
        ];
    }

    public function testBadCollation()
    {
        $this->expectException('Sabre\DAV\Exception\BadRequest');
        StringUtil::textMatch('foobar', 'foo', 'blabla', 'contains');
    }

    public function testBadMatchType()
    {
        $this->expectException('Sabre\DAV\Exception\BadRequest');
        StringUtil::textMatch('foobar', 'foo', 'i;octet', 'booh');
    }

    public function testEnsureUTF8_ascii()
    {
        $inputString = 'harkema';
        $outputString = 'harkema';

        $this->assertEquals(
            $outputString,
            StringUtil::ensureUTF8($inputString)
        );
    }

    public function testEnsureUTF8_latin1()
    {
        $inputString = "m\xfcnster";
        $outputString = 'münster';

        $this->assertEquals(
            $outputString,
            StringUtil::ensureUTF8($inputString)
        );
    }

    public function testEnsureUTF8_utf8()
    {
        $inputString = "m\xc3\xbcnster";
        $outputString = 'münster';

        $this->assertEquals(
            $outputString,
            StringUtil::ensureUTF8($inputString)
        );
    }
}
