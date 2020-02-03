<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    protected $caldavBackend;
    protected $principalUri;
    protected $notification;

    public function getInstance()
    {
        $this->principalUri = 'principals/user1';

        $this->notification = new CalDAV\Xml\Notification\SystemStatus(1, '"1"');

        $this->caldavBackend = new CalDAV\Backend\MockSharing([], [], [
            'principals/user1' => [
                $this->notification,
            ],
        ]);

        return new Collection($this->caldavBackend, $this->principalUri);
    }

    public function testGetChildren()
    {
        $col = $this->getInstance();
        $this->assertEquals('notifications', $col->getName());

        $this->assertEquals([
            new Node($this->caldavBackend, $this->principalUri, $this->notification),
        ], $col->getChildren());
    }

    public function testGetOwner()
    {
        $col = $this->getInstance();
        $this->assertEquals('principals/user1', $col->getOwner());
    }

    public function testGetGroup()
    {
        $col = $this->getInstance();
        $this->assertNull($col->getGroup());
    }

    public function testGetACL()
    {
        $col = $this->getInstance();
        $expected = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];

        $this->assertEquals($expected, $col->getACL());
    }

    public function testSetACL()
    {
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $col = $this->getInstance();
        $col->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        $col = $this->getInstance();
        $this->assertNull($col->getSupportedPrivilegeSet());
    }
}
