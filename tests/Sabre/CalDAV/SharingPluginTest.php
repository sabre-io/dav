<?php

class Sabre_CalDAV_SharingPluginTest extends Sabre_DAVServerTest {

    protected $setupCalDAV = true;
    protected $sharingPlugin;

    function setup() {

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
