<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV;

class NodeTest extends \PHPUnit\Framework\TestCase
{
    protected $systemStatus;
    protected $caldavBackend;

    public function getInstance()
    {
        $principalUri = 'principals/user1';

        $this->systemStatus = new CalDAV\Xml\Notification\SystemStatus(1, '"1"');

        $this->caldavBackend = new CalDAV\Backend\MockSharing([], [], [
            'principals/user1' => [
                $this->systemStatus,
            ],
        ]);

        $node = new Node($this->caldavBackend, 'principals/user1', $this->systemStatus);

        return $node;
    }

    public function testGetId()
    {
        $node = $this->getInstance();
        self::assertEquals($this->systemStatus->getId().'.xml', $node->getName());
    }

    public function testGetEtag()
    {
        $node = $this->getInstance();
        self::assertEquals('"1"', $node->getETag());
    }

    public function testGetNotificationType()
    {
        $node = $this->getInstance();
        self::assertEquals($this->systemStatus, $node->getNotificationType());
    }

    public function testDelete()
    {
        $node = $this->getInstance();
        $node->delete();
        self::assertEquals([], $this->caldavBackend->getNotificationsForPrincipal('principals/user1'));
    }

    public function testGetGroup()
    {
        $node = $this->getInstance();
        self::assertNull($node->getGroup());
    }

    public function testGetACL()
    {
        $node = $this->getInstance();
        $expected = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];

        self::assertEquals($expected, $node->getACL());
    }

    public function testSetACL()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $node = $this->getInstance();
        $node->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        $node = $this->getInstance();
        self::assertNull($node->getSupportedPrivilegeSet());
    }
}
