<?php

class Sabre_CalDAV_ShareableCalendarTest extends PHPUnit_Framework_TestCase {

    protected $backend;
    protected $instance;

    function setUp() {

        $props = array(
            'id' => 1,
        );

        $this->backend = new Sabre_CalDAV_Backend_Mock(
            array($props),
            array(),
            array()
        );
        $this->backend->updateShares(1, array(
            array(
                'href' => 'mailto:removeme@example.org',
                'commonName' => 'To be removed',
                'readOnly' => true,
            ),
        ), array()); 

        $pBackend = new Sabre_DAVACL_MockPrincipalBackend();


        $this->instance = new Sabre_CalDAV_ShareableCalendar($pBackend, $this->backend, $props); 

    }

    function testUpdateShares() {

        $this->instance->updateShares(array(
            array(
                'href' => 'mailto:test@example.org',
                'commonName' => 'Foo Bar',
                'summary' => 'Booh',
                'readOnly' => false,
            ),
        ), array('mailto:removeme@example.org'));

        $this->assertEquals(array(array(
            'href' => 'mailto:test@example.org',
            'commonName' => 'Foo Bar',
            'summary' => 'Booh',
            'readOnly' => false,
            'status' => Sabre_CalDAV_SharingPlugin::STATUS_NORESPONSE,
        )), $this->instance->getShares());

    }

    function testPublish() {

        $this->instance->setPublishStatus(true);
        $this->instance->setPublishStatus(false);

    }
}
