<?php

namespace Sabre\CalDAV;

use Sabre\DAV\Sharing;

class SharedCalendarTest extends \PHPUnit_Framework_TestCase {

    protected $backend;

    function getInstance(array $props = null) {

        if (is_null($props)) {
            $props = [
                'id'                                        => 1,
                '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/original',
                '{http://sabredav.org/ns}owner-principal'   => 'principals/owner',
                '{http://sabredav.org/ns}read-only'         => false,
                'share-access'                              => Sharing\Plugin::ACCESS_READWRITE,
                'principaluri'                              => 'principals/sharee',
            ];
        }

        $this->backend = new Backend\MockSharing(
            [$props],
            [],
            []
        );
        $this->backend->updateShares(1, [
            [
                'href'       => 'mailto:removeme@example.org',
                'commonName' => 'To be removed',
                'readOnly'   => true,
            ],
        ], []);

        return new SharedCalendar($this->backend, $props);

    }

    function testGetShares() {

        $this->assertEquals([[
            'href'       => 'mailto:removeme@example.org',
            'commonName' => 'To be removed',
            'readOnly'   => true,
            'status'     => SharingPlugin::STATUS_NORESPONSE,
        ]], $this->getInstance()->getShares());

    }

    function testGetOwner() {
        $this->assertEquals('principals/sharee', $this->getInstance()->getOwner());
    }

    function testGetACL() {

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
                'privilege' => '{' . Plugin::NS_CALDAV . '}read-free-busy',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ];

        $this->assertEquals($expected, $this->getInstance()->getACL());

    }

    function testGetChildACL() {

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

        $this->assertEquals($expected, $this->getInstance()->getChildACL());

    }

    function testUpdateShares() {

        $instance = $this->getInstance();
        $instance->updateShares([
            [
                'href'       => 'mailto:test@example.org',
                'commonName' => 'Foo Bar',
                'summary'    => 'Booh',
                'readOnly'   => false,
            ],
        ], ['mailto:removeme@example.org']);

        $this->assertEquals([[
            'href'       => 'mailto:test@example.org',
            'commonName' => 'Foo Bar',
            'summary'    => 'Booh',
            'readOnly'   => false,
            'status'     => SharingPlugin::STATUS_NORESPONSE,
        ]], $instance->getShares());

    }

    function testPublish() {

        $instance = $this->getInstance();
        $this->assertNull($instance->setPublishStatus(true));
        $this->assertNull($instance->setPublishStatus(false));

    }
}
