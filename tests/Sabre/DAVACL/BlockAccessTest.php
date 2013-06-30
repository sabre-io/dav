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

        $nodes = [
            new DAV\SimpleCollection('testdir'),
        ];

        $this->server = new DAV\Server($nodes);
        $this->plugin = new Plugin();
        $this->plugin->allowAccessToNodesWithoutACL = false;
        $this->server->addPlugin($this->plugin);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testGet() {

        $this->server->emit('beforeMethod', ['GET','testdir']);

    }

    function testGetDoesntExist() {

        $r = $this->server->emit('beforeMethod', ['GET','foo']);
        $this->assertTrue($r);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testHEAD() {

        $this->server->emit('beforeMethod', ['HEAD','testdir']);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testOPTIONS() {

        $this->server->emit('beforeMethod', ['OPTIONS','testdir']);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testPUT() {

        $this->server->emit('beforeMethod', ['PUT','testdir']);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testPROPPATCH() {

        $this->server->emit('beforeMethod', ['PROPPATCH','testdir']);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testCOPY() {

        $this->server->emit('beforeMethod', ['COPY','testdir']);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testMOVE() {

        $this->server->emit('beforeMethod', ['MOVE','testdir']);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testACL() {

        $this->server->emit('beforeMethod', ['ACL','testdir']);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testLOCK() {

        $this->server->emit('beforeMethod', ['LOCK','testdir']);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testBeforeBind() {

        $this->server->emit('beforeBind', ['testdir/file']);

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testBeforeUnbind() {

        $this->server->emit('beforeUnbind', ['testdir']);

    }

    function testBeforeGetProperties() {

        $requestedProperties = [
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
            '{DAV:}bar',
            '{DAV:}owner',
        ];
        $returnedProperties = [];

        $arguments = [
            'testdir',
            new DAV\SimpleCollection('testdir'),
            &$requestedProperties,
            &$returnedProperties
        ];
        $r = $this->server->emit('beforeGetProperties',$arguments);
        $this->assertTrue($r);

        $expected = [
            '403' => [
                '{DAV:}displayname' => null,
                '{DAV:}getcontentlength' => null,
                '{DAV:}bar' => null,
                '{DAV:}owner' => null,
            ],
        ];

        $this->assertEquals($expected, $returnedProperties);
        $this->assertEquals([], $requestedProperties);

    }

    function testBeforeGetPropertiesNoListing() {

        $this->plugin->hideNodesFromListings = true;

        $requestedProperties = [
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
            '{DAV:}bar',
            '{DAV:}owner',
        ];
        $returnedProperties = [];

        $arguments = [
            'testdir',
            new DAV\SimpleCollection('testdir'),
            &$requestedProperties,
            &$returnedProperties
        ];
        $r = $this->server->emit('beforeGetProperties',$arguments);
        $this->assertFalse($r);

    }
}
