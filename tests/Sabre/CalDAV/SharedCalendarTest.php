<?php

namespace Sabre\CalDAV;

class SharedCalendarTest extends \PHPUnit_Framework_TestCase {

    protected $backend;

    function getInstance(array $props = null) {

        if (is_null($props)) {
            $props = [
                'id'                                        => 1,
                '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/original',
                '{http://sabredav.org/ns}owner-principal'   => 'principals/owner',
                '{http://sabredav.org/ns}read-only'         => false,
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

    function testGetSharedUrl() {
        $this->assertEquals('calendars/owner/original', $this->getInstance()->getSharedUrl());
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
        $this->assertEquals('principals/owner', $this->getInstance()->getOwner());
    }

    function testGetACL() {

        $expected = [
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner',
                'protected' => true,
            ],

            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{' . Plugin::NS_CALDAV . '}read-free-busy',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/owner/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
        ];

        $this->assertEquals($expected, $this->getInstance()->getACL());

    }

    function testGetChildACL() {

        $expected = [
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/owner/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
        ];

        $this->assertEquals($expected, $this->getInstance()->getChildACL());

    }

    function testGetChildACLReadOnly() {

        $expected = [
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/owner/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/sharee',
                'protected' => true,
            ],
        ];

        $props = [
            'id'                                        => 1,
            '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/original',
            '{http://sabredav.org/ns}owner-principal'   => 'principals/owner',
            '{http://sabredav.org/ns}read-only'         => true,
            'principaluri'                              => 'principals/sharee',
        ];
        $this->assertEquals($expected, $this->getInstance($props)->getChildACL());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testCreateInstanceMissingArg() {

        $this->getInstance([
            'id'                                        => 1,
            '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/original',
            '{http://sabredav.org/ns}read-only'         => false,
            'principaluri'                              => 'principals/sharee',
        ]);

    }

}
