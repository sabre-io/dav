<?php

class Sabre_CalDAV_Property_ScheduleCalendarTranspTest extends PHPUnit_Framework_TestCase {

    function testSimple() {

        $sccs = new Sabre_CalDAV_Property_ScheduleCalendarTransp('transparent');
        $this->assertEquals('transparent', $sccs->getValue());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testBadArg() {

        $sccs = new Sabre_CalDAV_Property_ScheduleCalendarTransp('foo');

    }

    function values() {

        return array(
            array('transparent'),
            array('opaque'),
        );

    }

    /**
     * @depends testSimple
     * @dataProvider values
     */
    function testSerialize($value) {

        $property = new Sabre_CalDAV_Property_ScheduleCalendarTransp($value);

        $doc = new DOMDocument();
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cal',Sabre_CalDAV_Plugin::NS_CALDAV);

        $doc->appendChild($root);
        $objectTree = new Sabre_DAV_ObjectTree(new Sabre_DAV_SimpleCollection('rootdir'));
        $server = new Sabre_DAV_Server($objectTree);

        $property->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . Sabre_CalDAV_Plugin::NS_CALDAV . '">' .
'<cal:' . $value . '/>' .
'</d:root>
', $xml);

    }

    /**
     * @depends testSimple
     * @dataProvider values
     */
    function testUnserializer($value) {

        $xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . Sabre_CalDAV_Plugin::NS_CALDAV . '">' .
'<cal:'.$value.'/>' .
'</d:root>';

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);

        $property = Sabre_CalDAV_Property_ScheduleCalendarTransp::unserialize($dom->firstChild);

        $this->assertTrue($property instanceof Sabre_CalDAV_Property_ScheduleCalendarTransp);
        $this->assertEquals($value, $property->getValue());

    }

    /**
     * @depends testSimple
     */
    function testUnserializerBadData() {

        $xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . Sabre_CalDAV_Plugin::NS_CALDAV . '">' .
'<cal:foo/>' .
'</d:root>';

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);

        $this->assertNull(Sabre_CalDAV_Property_ScheduleCalendarTransp::unserialize($dom->firstChild));

    }
}
