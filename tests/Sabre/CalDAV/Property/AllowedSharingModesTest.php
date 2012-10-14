<?php

class Sabre_CalDAV_Property_AllowedSharingModesTest extends PHPUnit_Framework_TestCase {

    function testSimple() {

        $sccs = new Sabre_CalDAV_Property_AllowedSharingModes(true,true);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new Sabre_CalDAV_Property_AllowedSharingModes(true,true);

        $doc = new DOMDocument();
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cal',Sabre_CalDAV_Plugin::NS_CALDAV);
        $root->setAttribute('xmlns:cs',Sabre_CalDAV_Plugin::NS_CALENDARSERVER);

        $doc->appendChild($root);
        $server = new Sabre_DAV_Server();

        $property->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . Sabre_CalDAV_Plugin::NS_CALDAV . '" xmlns:cs="' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '">' .
'<cs:can-be-shared/>' .
'<cs:can-be-published/>' .
'</d:root>
', $xml);

    }

}
