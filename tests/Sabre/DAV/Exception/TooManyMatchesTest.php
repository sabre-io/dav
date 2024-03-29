<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

use DOMDocument;
use Sabre\DAV;

class TooManyMatchesTest extends \PHPUnit\Framework\TestCase
{
    public function testSerialize()
    {
        $dom = new DOMDocument('1.0');
        $dom->formatOutput = true;
        $root = $dom->createElement('d:root');

        $dom->appendChild($root);
        $root->setAttribute('xmlns:d', 'DAV:');

        $locked = new TooManyMatches();

        $locked->serialize(new DAV\Server(), $root);

        $output = $dom->saveXML();

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:number-of-matches-within-limits xmlns:d="DAV:"/>
</d:root>
';

        self::assertEquals($expected, $output);
    }
}
