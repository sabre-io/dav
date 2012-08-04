<?php

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV;

class CollectionTest extends \PHPUnit_Framework_TestCase {

    function testGetChildren() {

        $principalUri = 'principals/user1';

        $systemStatus = new Notification\SystemStatus(1);

        $caldavBackend = new CalDAV\Backend\Mock(array(),array(), array(
            'principals/user1' => array(
                $systemStatus
            )
        )); 


        $col = new Collection($caldavBackend, $principalUri);
        $this->assertEquals('notifications', $col->getName());

        $this->assertEquals(array(
            new Node($caldavBackend, $systemStatus)
        ), $col->getChildren()); 

    }

}
