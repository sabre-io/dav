<?php declare (strict_types=1);

namespace Sabre\CalDAV\Subscriptions;

use Sabre\DAV\PropFind;
use Sabre\DAV\Server;

class PluginTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $server = new Server(null, null, null, function(){});
        $plugin = new Plugin();

        $server->addPlugin($plugin);

        $this->assertEquals(
            '{http://calendarserver.org/ns/}subscribed',
            $server->resourceTypeMapping['Sabre\\CalDAV\\Subscriptions\\ISubscription']
        );
        $this->assertEquals(
            'Sabre\\DAV\\Xml\\Property\\Href',
            $server->xml->elementMap['{http://calendarserver.org/ns/}source']
        );

        $this->assertEquals(
            ['calendarserver-subscribed'],
            $plugin->getFeatures()
        );

        $this->assertEquals(
            'subscriptions',
            $plugin->getPluginInfo()['name']
        );

    }

    function testPropFind() {

        $propName = '{http://calendarserver.org/ns/}subscribed-strip-alarms';
        $propFind = new PropFind('foo', [$propName]);
        $propFind->set($propName, null, 200);

        $plugin = new Plugin();
        $plugin->propFind($propFind, new \Sabre\DAV\SimpleCollection('hi'));

        $this->assertFalse(is_null($propFind->get($propName)));

    }

}
