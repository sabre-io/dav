<?php

class Sabre_VObject_Issue154Test extends PHPUnit_Framework_TestCase {

    function testStuff() {

        $vcard = new Sabre_VObject_Component('VCARD');
        $vcard->VERSION = '3.0';
        $vcard->UID = 'foo-bar';
        $vcard->PHOTO = base64_encode('random_stuff');
        $vcard->PHOTO->add('BASE64',null);

        $result = $vcard->serialize();
        $expected = array(
            "BEGIN:VCARD",
            "VERSION:3.0",
            "PHOTO;BASE64:" . base64_encode('random_stuff'),
            "UID:foo-bar",
            "END:VCARD",
            "",
        );

        $this->assertEquals(implode("\r\n", $expected), $result);

    }

}
