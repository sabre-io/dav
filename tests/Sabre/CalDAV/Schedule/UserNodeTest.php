<?php

class Sabre_CalDAV_Schedule_UserNodeTest extends PHPUnit_Framework_TestCase {

    function testAll() {

        $userNode = new Sabre_CalDAV_Schedule_UserNode('principal/uri');
        $this->assertEquals('uri', $userNode->getName());

        $this->assertEquals(array(
            new Sabre_CalDAV_Schedule_Inbox('principal/uri'),
            new Sabre_CalDAV_Schedule_Outbox('principal/uri')
        ), $userNode->getChildren());


    }

}

?>
