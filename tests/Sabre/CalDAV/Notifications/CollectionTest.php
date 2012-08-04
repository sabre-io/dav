<?php

class Sabre_CalDAV_Notifications_CollectionTest extends \PHPUnit_Framework_TestCase {

    function testGetChildren() {

        $principalUri = 'principals/user1';

        $systemStatus = new Sabre_CalDAV_Notifications_Notification_SystemStatus(1);

        $caldavBackend = new Sabre_CalDAV_Backend_Mock(array(),array(), array(
            'principals/user1' => array(
                $systemStatus
            )
        )); 


        $col = new Sabre_CalDAV_Notifications_Collection($caldavBackend, $principalUri);
        $this->assertEquals('notifications', $col->getName());

        $this->assertEquals(array(
            new Sabre_CalDAV_Notifications_Node($caldavBackend, $systemStatus)
        ), $col->getChildren()); 

    }

}
