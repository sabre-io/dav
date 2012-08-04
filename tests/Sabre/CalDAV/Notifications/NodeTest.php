<?php

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV;

class NodeTest extends \PHPUnit_Framework_TestCase {

    function testGetId() {

        $principalUri = 'principals/user1';

        $systemStatus = new Notification\SystemStatus(1);

        $caldavBackend = new CalDAV\Backend\Mock(array(),array(), array(
            'principals/user1' => array(
                $systemStatus
            )
        )); 


        $node = new Node($caldavBackend, $systemStatus);
        $this->assertEquals($systemStatus->getId(), $node->getName());

    }

}
