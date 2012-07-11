<?php

require_once 'Sabre/CalDAV/Backend/SharingMock.php';

class Sabre_CalDAV_SharingPluginTest extends Sabre_DAVServerTest {

    protected $setupCalDAV = true;
    protected $sharingPlugin;

    function setup() {

        $this->caldavBackend = new Sabre_CalDAV_Backend_SharingMock();
        parent::setup();
        $this->server->addPlugin(
            $this->sharingPlugin = new Sabre_CalDAV_SharingPlugin()
        );

    }

    function testSimple() {

        $this->assertEquals(array('calendarserver-sharing'), $this->sharingPlugin->getFeatures());
        $this->assertEquals('caldav-sharing', $this->sharingPlugin->getPluginName());

    }

}
