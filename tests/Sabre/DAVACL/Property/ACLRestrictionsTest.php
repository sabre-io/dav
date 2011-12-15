<?php

class Sabre_DAVACL_Property_ACLRestrictionsTest extends PHPUnit_Framework_TestCase {

    function testConstruct() {

        $prop = new Sabre_DAVACL_Property_AclRestrictions();

    }

    function testSerializeEmpty() {

        $dom = new DOMDocument('1.0');
        $root = $dom->createElementNS('DAV:','d:root');

        $dom->appendChild($root);

        $acl = new Sabre_DAVACL_Property_AclRestrictions();
        $acl->serialize(new Sabre_DAV_Server(), $root);

        $xml = $dom->saveXML();
        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:"><d:grant-only/><d:no-invert/></d:root>
';
        $this->assertEquals($expected, $xml);

    }


}
