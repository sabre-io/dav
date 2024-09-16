<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Subscriptions;

use Sabre\DAV\PropFind;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    public function testInit()
    {
        $server = new \Sabre\DAV\Server();
        $plugin = new Plugin();

        $server->addPlugin($plugin);

        self::assertEquals(
            '{http://calendarserver.org/ns/}subscribed',
            $server->resourceTypeMapping[\Sabre\CalDAV\Subscriptions\ISubscription::class]
        );
        self::assertEquals(
            \Sabre\DAV\Xml\Property\Href::class,
            $server->xml->elementMap['{http://calendarserver.org/ns/}source']
        );

        self::assertEquals(
            ['calendarserver-subscribed'],
            $plugin->getFeatures()
        );

        self::assertEquals(
            'subscriptions',
            $plugin->getPluginInfo()['name']
        );
    }

    public function testPropFind()
    {
        $propName = '{http://calendarserver.org/ns/}subscribed-strip-alarms';
        $propFind = new PropFind('foo', [$propName]);
        $propFind->set($propName, null, 200);

        $plugin = new Plugin();
        $plugin->propFind($propFind, new \Sabre\DAV\SimpleCollection('hi'));

        self::assertFalse(is_null($propFind->get($propName)));
    }
}
