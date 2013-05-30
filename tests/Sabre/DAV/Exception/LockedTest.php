<?php

class Sabre_DAV_Exception_LockedTest extends PHPUnit_Framework_TestCase {

    function testSerialize() {

        $dom = new DOMDocument('1.0');
        $dom->formatOutput = true;
        $root = $dom->createElement('d:root');

        $dom->appendChild($root);
        $root->setAttribute('xmlns:d','DAV:');

        $lockInfo = new Sabre_DAV_Locks_LockInfo();
        $lockInfo->uri = '/foo';
        $locked = new Sabre_DAV_Exception_Locked($lockInfo);

        $locked->serialize(new Sabre_DAV_Server(), $root);

        $output = $dom->saveXML();

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:lock-token-submitted xmlns:d="DAV:">
    <d:href>/foo</d:href>
  </d:lock-token-submitted>
</d:root>
';

        $this->assertEquals($expected, $output);

    }

    function testSerializeAmpersand() {

        $dom = new DOMDocument('1.0');
        $dom->formatOutput = true;
        $root = $dom->createElement('d:root');

        $dom->appendChild($root);
        $root->setAttribute('xmlns:d','DAV:');

        $lockInfo = new Sabre_DAV_Locks_LockInfo();
        $lockInfo->uri = '/foo&bar';
        $locked = new Sabre_DAV_Exception_Locked($lockInfo);

        $locked->serialize(new Sabre_DAV_Server(), $root);

        $output = $dom->saveXML();

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:lock-token-submitted xmlns:d="DAV:">
    <d:href>/foo&amp;bar</d:href>
  </d:lock-token-submitted>
</d:root>
';

        $this->assertEquals($expected, $output);

    }
}
