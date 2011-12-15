<?php

class Sabre_CalDAV_Schedule_InboxTest extends PHPUnit_Framework_TestCase {

    function testSetup() {

        $inbox = new Sabre_CalDAV_Schedule_Inbox();
        $this->assertEquals('inbox', $inbox->getName());
        $this->assertEquals(array(), $inbox->getChildren());

    }

}
