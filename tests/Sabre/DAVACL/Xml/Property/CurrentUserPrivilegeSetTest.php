<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\XMLUtil;
use Sabre\HTTP;
use Sabre\Xml\Reader;


class CurrentUserPrivilegeSetTest extends \PHPUnit_Framework_TestCase {

    function testSerialize() {

        $privileges = [ 
            '{DAV:}read',
            '{DAV:}write',
        ];
        $prop = new CurrentUserPrivilegeSet($privileges);
        $xml = (new DAV\Server())->xml->write(['{DAV:}root' => $prop]);

        $xpaths = array(
            '/d:root' => 1,
            '/d:root/d:privilege' => 2,
            '/d:root/d:privilege/d:read' => 1,
            '/d:root/d:privilege/d:write' => 1,
        );

        $dom2 = XMLUtil::loadDOMDocument($xml);

        $dxpath = new \DOMXPath($dom2);
        $dxpath->registerNamespace('d','urn:DAV');
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

        $result = $this->parse($source);
        $this->assertTrue($result->has('{DAV:}read'));
        $this->assertTrue($result->has('{DAV:}write-properties'));
        $this->assertFalse($result->has('{DAV:}bind'));

    }

    function parse($xml) {

        $reader = new Reader();
        $reader->elementMap['{DAV:}root'] = 'Sabre\\DAVACL\\Xml\\Property\\CurrentUserPrivilegeSet';
        $reader->xml($xml);
        $result = $reader->parse();
        return $result['value'];

    }

}
