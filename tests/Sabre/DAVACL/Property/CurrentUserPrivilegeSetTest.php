<?php

class Sabre_DAVACL_Property_CurrentUserPrivilegeSetTest extends PHPUnit_Framework_TestCase {

    function testSerialize() {

        $privileges = array(
            '{DAV:}read',
            '{DAV:}write',
        );
        $prop = new Sabre_DAVACL_Property_CurrentUserPrivilegeSet($privileges);

        $server = new Sabre_DAV_Server();
        $dom = new DOMDocument('1.0','utf-8');
        $root = $dom->createElementNS('DAV:','d:root');
        $dom->appendChild($root);

        $prop->serialize($server, $root);

        $xpaths = array(
            '/d:root' => 1,
            '/d:root/d:privilege' => 2,
            '/d:root/d:privilege/d:read' => 1,
            '/d:root/d:privilege/d:write' => 1,
        );

        // Reloading because PHP DOM sucks
        $dom2 = new DOMDocument('1.0', 'utf-8');
        $dom2->loadXML($dom->saveXML());

        $dxpath = new DOMXPath($dom2);
        $dxpath->registerNamespace('d','DAV:');
        foreach($xpaths as $xpath=>$count) {

            $this->assertEquals($count, $dxpath->query($xpath)->length, 'Looking for : ' . $xpath . ', we could only find ' . $dxpath->query($xpath)->length . ' elements, while we expected ' . $count);

        }

    }

    function testUnserialize() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
    <d:privilege>
        <d:write-properties />
    </d:privilege>
    <d:privilege>
        <d:read />
    </d:privilege>
</d:root>
';

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($source);
        $result = Sabre_DAVACL_Property_CurrentUserPrivilegeSet::unserialize($dom->firstChild, array());
        $this->assertTrue($result->has('{DAV:}read'));
        $this->assertTrue($result->has('{DAV:}write-properties'));
        $this->assertFalse($result->has('{DAV:}bind'));

    }

}
