<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

use Sabre\DAV;

class LockedTest extends \PHPUnit\Framework\TestCase
{
    public function testSerialize()
    {
        $dom = new \DOMDocument('1.0');
        $dom->formatOutput = true;
        $root = $dom->createElement('d:root');

        $dom->appendChild($root);
        $root->setAttribute('xmlns:d', 'DAV:');

        $lockInfo = new DAV\Locks\LockInfo();
        $lockInfo->uri = '/foo';
        $locked = new Locked($lockInfo);

        $locked->serialize(new DAV\Server(), $root);

        $output = $dom->saveXML();

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:lock-token-submitted xmlns:d="DAV:">
    <d:href>/foo</d:href>
  </d:lock-token-submitted>
</d:root>
';

        self::assertEquals($expected, $output);
    }

    public function testSerializeAmpersand()
    {
        $dom = new \DOMDocument('1.0');
        $dom->formatOutput = true;
        $root = $dom->createElement('d:root');

        $dom->appendChild($root);
        $root->setAttribute('xmlns:d', 'DAV:');

        $lockInfo = new DAV\Locks\LockInfo();
        $lockInfo->uri = '/foo&bar';
        $locked = new Locked($lockInfo);

        $locked->serialize(new DAV\Server(), $root);

        $output = $dom->saveXML();

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:lock-token-submitted xmlns:d="DAV:">
    <d:href>/foo&amp;bar</d:href>
  </d:lock-token-submitted>
</d:root>
';

        self::assertEquals($expected, $output);
    }
}
