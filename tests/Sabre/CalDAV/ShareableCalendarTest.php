<?php

namespace Sabre\CalDAV;

class ShareableCalendarTest extends \PHPUnit_Framework_TestCase {

    protected $backend;
    protected $instance;

    function setUp() {

        $props = [
            'id' => 1,
        ];

        $this->backend = new Backend\MockSharing(
            [$props]
        );
        $this->backend->updateShares(1, [
            [
                'href'       => 'mailto:removeme@example.org',
                'commonName' => 'To be removed',
                'readOnly'   => true,
            ],
        ], []);

        $this->instance = new ShareableCalendar($this->backend, $props);

    }

    function testUpdateShares() {

        $this->instance->updateShares([
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
        ]], $this->instance->getShares());

    }

    function testPublish() {

        $this->assertNull($this->instance->setPublishStatus(true));
        $this->assertNull($this->instance->setPublishStatus(false));

    }
}
