<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAV\Sharing;
use Sabre\DAV\Xml\Element\Sharee;

class SharedCalendarTest extends \PHPUnit\Framework\TestCase
{
    protected $backend;

    public function getInstance(array $props = null)
    {
        if (is_null($props)) {
            $props = [
                'id' => 1,
                '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/original',
                '{http://sabredav.org/ns}owner-principal' => 'principals/owner',
                '{http://sabredav.org/ns}read-only' => false,
                'share-access' => Sharing\Plugin::ACCESS_READWRITE,
                'principaluri' => 'principals/sharee',
            ];
        }

        $this->backend = new Backend\MockSharing(
            [$props],
            [],
            []
        );

        $sharee = new Sharee();
        $sharee->href = 'mailto:removeme@example.org';
        $sharee->properties['{DAV:}displayname'] = 'To be removed';
        $sharee->access = Sharing\Plugin::ACCESS_READ;
        $this->backend->updateInvites(1, [$sharee]);

        return new SharedCalendar($this->backend, $props);
    }

    public function testGetInvites()
    {
        $sharee = new Sharee();
        $sharee->href = 'mailto:removeme@example.org';
        $sharee->properties['{DAV:}displayname'] = 'To be removed';
        $sharee->access = Sharing\Plugin::ACCESS_READ;
        $sharee->inviteStatus = Sharing\Plugin::INVITE_NORESPONSE;

        self::assertEquals(
            [$sharee],
            $this->getInstance()->getInvites()
        );
    }

    public function testGetOwner()
    {
        self::assertEquals('principals/sharee', $this->getInstance()->getOwner());
    }

    public function testGetACL()
    {
        $expected = [
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/sharee/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write-properties',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write-properties',
                'principal' => 'principals/sharee/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{'.Plugin::NS_CALDAV.'}read-free-busy',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ];

        self::assertEquals($expected, $this->getInstance()->getACL());
    }

    public function testGetChildACL()
    {
        $expected = [
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/sharee/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee/calendar-proxy-read',
                'protected' => true,
            ],
        ];

        self::assertEquals($expected, $this->getInstance()->getChildACL());
    }

    public function testUpdateInvites()
    {
        $instance = $this->getInstance();
        $newSharees = [
            new Sharee(),
            new Sharee(),
        ];
        $newSharees[0]->href = 'mailto:test@example.org';
        $newSharees[0]->properties['{DAV:}displayname'] = 'Foo Bar';
        $newSharees[0]->comment = 'Booh';
        $newSharees[0]->access = Sharing\Plugin::ACCESS_READWRITE;

        $newSharees[1]->href = 'mailto:removeme@example.org';
        $newSharees[1]->access = Sharing\Plugin::ACCESS_NOACCESS;

        $instance->updateInvites($newSharees);

        $expected = [
            clone $newSharees[0],
        ];
        $expected[0]->inviteStatus = Sharing\Plugin::INVITE_NORESPONSE;
        self::assertEquals($expected, $instance->getInvites());
    }

    public function testPublish()
    {
        $instance = $this->getInstance();
        self::assertNull($instance->setPublishStatus(true));
        self::assertNull($instance->setPublishStatus(false));
    }
}
