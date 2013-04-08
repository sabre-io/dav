<?php

namespace Sabre\CalDAV;

use Sabre\DAVACL;

require_once 'Sabre/CalDAV/TestUtil.php';

/**
 * @covers Sabre\CalDAV\UserCalendars
 */
class UserCalendarsSubscriptionsTest extends \PHPUnit_Framework_TestCase {

    protected $backend;

    function getInstance() {

        $this->backend = new Backend\SubscriptionMock([], []);
        $caldavBackend->createSubscription('principals/user1', 'uri', array_merge($info, $override));

        return new UserCalendars($this->backend, 'principals/user1');

    }

    function testSimple() {

        $instance = $this->getInstance();
        $this->assertEquals('user1', $instance->getName());

    }

    function testGetChildren() {

        $instance = $this->getInstance();
        $children = $instance->getChildren();
        $this->assertEquals(1, count($children));
        $this->assertInstanceOf('Sabre\\CalDAV\\Subscriptions\\Subscription', $children[1]);

    }

    function testCreateSubscription() {

        $instance = $this->getInstance();
        $rt = ['{DAV:}collection', '{http://calendarserver.org/ns/}subscribed'];

        $props = [
            '{DAV:}displayname' => 'baz',
        ];
        $instance->createExtendedCollection('sub2', $rt, $props);

    }

}
