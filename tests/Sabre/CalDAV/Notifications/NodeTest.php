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
        $this->assertEquals($this->systemStatus->getId().'.xml', $node->getName());
    }

    public function testGetEtag()
    {
        $node = $this->getInstance();
        $this->assertEquals('"1"', $node->getETag());
    }

    public function testGetNotificationType()
    {
        $node = $this->getInstance();
        $this->assertEquals($this->systemStatus, $node->getNotificationType());
    }

    public function testDelete()
    {
        $node = $this->getInstance();
        $node->delete();
        $this->assertEquals([], $this->caldavBackend->getNotificationsForPrincipal('principals/user1'));
    }

    public function testGetGroup()
    {
        $node = $this->getInstance();
        $this->assertNull($node->getGroup());
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

        $this->assertEquals($expected, $node->getACL());
    }

    public function testSetACL()
    {
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $node = $this->getInstance();
        $node->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        $node = $this->getInstance();
        $this->assertNull($node->getSupportedPrivilegeSet());
    }
}
