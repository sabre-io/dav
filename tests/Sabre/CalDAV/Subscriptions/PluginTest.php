<?php

namespace Sabre\CalDAV\Subscriptions;

class PluginTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $server = new \Sabre\DAV\Server();
        $plugin = new Plugin();

        $server->addPlugin($plugin);

        $this->assertEquals(
            '{http://calendarserver.org/ns/}subscribed',
            $server->resourceTypeMapping['Sabre\\CalDAV\\Subscriptions\\ISubscription']
        );
        $this->assertEquals(
            'Sabre\\DAV\\Property\\Href',
            $server->propertyMap['{http://calendarserver.org/ns/}source']
        );

        $this->assertEquals(
            ['calendarserver-subscribed'],
            $plugin->getFeatures()
        );

    }

}
