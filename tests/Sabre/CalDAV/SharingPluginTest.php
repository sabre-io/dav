<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\HTTP;

class SharingPluginTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $setupCalDAVSharing = true;
    protected $setupACL = true;
    protected $autoLogin = 'user1';

    function setUp() {

        $this->caldavCalendars = [];
        parent::setUp(); 

    }

    function testBasics() {

        $plugin = $this->caldavSharingPlugin;
        $this->assertEquals(
            [],
            $plugin->getFeatures()
        );
        $this->assertEquals(
            'caldav-sharing',
            $plugin->getPluginName()
        );
        $this->assertInternalType(
            'array',
            $plugin->getPluginInfo()
        );


    }

}
