<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class BlockAccessTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DAV\Server
     */
    protected $server;
    protected $plugin;

    function setUp() {

        $nodes = array(
            new DAV\SimpleCollection('testdir'),
        );

        $this->server = new DAV\Server($nodes);
        $this->plugin = new Plugin();
        $this->plugin->allowAccessToNodesWithoutACL = false;
        $this->server->addPlugin($this->plugin);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testGet() {

        $this->server->broadcastEvent('beforeMethod',array('GET','testdir'));

    }

    function testGetDoesntExist() {

        $r = $this->server->broadcastEvent('beforeMethod',array('GET','foo'));
        $this->assertTrue($r);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testHEAD() {

        $this->server->broadcastEvent('beforeMethod',array('HEAD','testdir'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testOPTIONS() {

        $this->server->broadcastEvent('beforeMethod',array('OPTIONS','testdir'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testPUT() {

        $this->server->broadcastEvent('beforeMethod',array('PUT','testdir'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testPROPPATCH() {

        $this->server->broadcastEvent('beforeMethod',array('PROPPATCH','testdir'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testCOPY() {

        $this->server->broadcastEvent('beforeMethod',array('COPY','testdir'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testMOVE() {

        $this->server->broadcastEvent('beforeMethod',array('MOVE','testdir'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testACL() {

        $this->server->broadcastEvent('beforeMethod',array('ACL','testdir'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testLOCK() {

        $this->server->broadcastEvent('beforeMethod',array('LOCK','testdir'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testBeforeBind() {

        $this->server->broadcastEvent('beforeBind',array('testdir/file'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testBeforeUnbind() {

        $this->server->broadcastEvent('beforeUnbind',array('testdir'));

    }

    function testBeforeGetProperties() {

        $requestedProperties = array(
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
            '{DAV:}bar',
            '{DAV:}owner',
        );
        $returnedProperties = array();

        $arguments = array(
            'testdir',
            new DAV\SimpleCollection('testdir'),
            &$requestedProperties,
            &$returnedProperties
        );
        $r = $this->server->broadcastEvent('beforeGetProperties',$arguments);
        $this->assertTrue($r);

        $expected = array(
            '403' => array(
                '{DAV:}displayname' => null,
                '{DAV:}getcontentlength' => null,
                '{DAV:}bar' => null,
                '{DAV:}owner' => null,
            ),
        );

        $this->assertEquals($expected, $returnedProperties);
        $this->assertEquals(array(), $requestedProperties);

    }

    function testBeforeGetPropertiesNoListing() {

        $this->plugin->hideNodesFromListings = true;

        $requestedProperties = array(
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
            '{DAV:}bar',
            '{DAV:}owner',
        );
        $returnedProperties = array();

        $arguments = array(
            'testdir',
            new DAV\SimpleCollection('testdir'),
            &$requestedProperties,
            &$returnedProperties
        );
        $r = $this->server->broadcastEvent('beforeGetProperties',$arguments);
        $this->assertFalse($r);

    }
}
