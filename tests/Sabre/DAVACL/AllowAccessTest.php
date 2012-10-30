<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class AllowAccessTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DAV\Server
     */
    protected $server;

    function setUp() {

        $nodes = array(
            new DAV\SimpleCollection('testdir'),
        );

        $this->server = new DAV\Server($nodes);
        $aclPlugin = new Plugin();
        $aclPlugin->allowAccessToNodesWithoutACL = true;
        $this->server->addPlugin($aclPlugin);

    }

    function testGet() {

        $this->assertTrue($this->server->broadcastEvent('beforeMethod',array('GET','testdir')));

    }

    function testGetDoesntExist() {

        $r = $this->server->broadcastEvent('beforeMethod',array('GET','foo'));
        $this->assertTrue($r);

    }

    function testHEAD() {

        $this->assertTrue($this->server->broadcastEvent('beforeMethod',array('HEAD','testdir')));

    }

    function testOPTIONS() {

        $this->assertTrue($this->server->broadcastEvent('beforeMethod',array('OPTIONS','testdir')));

    }

    function testPUT() {

        $this->assertTrue($this->server->broadcastEvent('beforeMethod',array('PUT','testdir')));

    }

    function testACL() {

        $this->assertTrue($this->server->broadcastEvent('beforeMethod',array('ACL','testdir')));

    }

    function testPROPPATCH() {

        $this->assertTrue($this->server->broadcastEvent('beforeMethod',array('PROPPATCH','testdir')));

    }

    function testCOPY() {

        $this->assertTrue($this->server->broadcastEvent('beforeMethod',array('COPY','testdir')));

    }

    function testMOVE() {

        $this->assertTrue($this->server->broadcastEvent('beforeMethod',array('MOVE','testdir')));

    }

    function testLOCK() {

        $this->assertTrue($this->server->broadcastEvent('beforeMethod',array('LOCK','testdir')));

    }

    function testBeforeBind() {

        $this->assertTrue($this->server->broadcastEvent('beforeBind',array('testdir/file')));

    }


    function testBeforeUnbind() {

        $this->assertTrue($this->server->broadcastEvent('beforeUnbind',array('testdir')));

    }

    function testAfterGetProperties() {

        $properties = array(
            'href' => 'foo',
            '200' => array(
                '{DAV:}displayname' => 'foo',
                '{DAV:}getcontentlength' => 500,
            ),
            '404' => array(
                '{DAV:}bar' => null,
            ),
            '403' => array(
                '{DAV:}owner' => null,
            ),
        );

        $expected = array(
            'href' => 'foo',
            '200' => array(
                '{DAV:}displayname' => 'foo',
                '{DAV:}getcontentlength' => 500,
            ),
            '404' => array(
                '{DAV:}bar' => null,
            ),
            '403' => array(
                '{DAV:}owner' => null,
            ),
        );

        $r = $this->server->broadcastEvent('afterGetProperties',array('testdir',&$properties));
        $this->assertTrue($r);

        $this->assertEquals($expected, $properties);

    }

}
