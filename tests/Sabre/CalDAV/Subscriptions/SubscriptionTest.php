<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Subscriptions;

use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Property\Href;

class SubscriptionTest extends \PHPUnit\Framework\TestCase
{
    protected $backend;

    public function getSub($override = [])
    {
        $caldavBackend = new \Sabre\CalDAV\Backend\MockSubscriptionSupport([], []);

        $info = [
            '{http://calendarserver.org/ns/}source' => new Href('http://example.org/src'),
            'lastmodified' => date('2013-04-06 11:40:00'), // tomorrow is my birthday!
            '{DAV:}displayname' => 'displayname',
        ];

        $id = $caldavBackend->createSubscription('principals/user1', 'uri', array_merge($info, $override));
        $subInfo = $caldavBackend->getSubscriptionsForUser('principals/user1');

        self::assertEquals(1, count($subInfo));
        $subscription = new Subscription($caldavBackend, $subInfo[0]);

        $this->backend = $caldavBackend;

        return $subscription;
    }

    public function testValues()
    {
        $sub = $this->getSub();

        self::assertEquals('uri', $sub->getName());
        self::assertEquals(date('2013-04-06 11:40:00'), $sub->getLastModified());
        self::assertEquals([], $sub->getChildren());

        self::assertEquals(
            [
                '{DAV:}displayname' => 'displayname',
                '{http://calendarserver.org/ns/}source' => new Href('http://example.org/src'),
            ],
            $sub->getProperties(['{DAV:}displayname', '{http://calendarserver.org/ns/}source'])
        );

        self::assertEquals('principals/user1', $sub->getOwner());
        self::assertNull($sub->getGroup());

        $acl = [
            [
                'privilege' => '{DAV:}all',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ],
        ];
        self::assertEquals($acl, $sub->getACL());

        self::assertNull($sub->getSupportedPrivilegeSet());
    }

    public function testValues2()
    {
        $sub = $this->getSub([
            'lastmodified' => null,
        ]);

        self::assertEquals(null, $sub->getLastModified());
    }

    public function testSetACL()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $sub = $this->getSub();
        $sub->setACL([]);
    }

    public function testDelete()
    {
        $sub = $this->getSub();
        $sub->delete();

        self::assertEquals([], $this->backend->getSubscriptionsForUser('principals1/user1'));
    }

    public function testUpdateProperties()
    {
        $sub = $this->getSub();
        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'foo',
        ]);
        $sub->propPatch($propPatch);
        self::assertTrue($propPatch->commit());

        self::assertEquals(
            'foo',
            $this->backend->getSubscriptionsForUser('principals/user1')[0]['{DAV:}displayname']
        );
    }

    public function testBadConstruct()
    {
        $this->expectException('InvalidArgumentException');
        $caldavBackend = new \Sabre\CalDAV\Backend\MockSubscriptionSupport([], []);
        new Subscription($caldavBackend, []);
    }
}
