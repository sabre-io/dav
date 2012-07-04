<?php

class Sabre_CalDAV_Notifications_NodeTest extends \PHPUnit_Framework_TestCase {

    function testGetId() {

        $principalUri = 'principals/user1';

        $systemStatus = new Sabre_CalDAV_Notifications_Notification_SystemStatus(1);

        $caldavBackend = new Sabre_CalDAV_Backend_Mock(array(),array(), array(
            'principals/user1' => array(
                $systemStatus
            )
        )); 


        $node = new Sabre_CalDAV_Notifications_Node($caldavBackend, $systemStatus);
        $this->assertEquals($systemStatus->getId(), $node->getName());

    }

}
