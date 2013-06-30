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

        $this->assertTrue($this->server->emit('beforeMethod', ['GET','testdir']));

    }

    function testGetDoesntExist() {

        $r = $this->server->emit('beforeMethod', ['GET','foo']);
        $this->assertTrue($r);

    }

    function testHEAD() {

        $this->assertTrue($this->server->emit('beforeMethod', ['HEAD','testdir']));

    }

    function testOPTIONS() {

        $this->assertTrue($this->server->emit('beforeMethod', ['OPTIONS','testdir']));

    }

    function testPUT() {

        $this->assertTrue($this->server->emit('beforeMethod', ['PUT','testdir']));

    }

    function testACL() {

        $this->assertTrue($this->server->emit('beforeMethod', ['ACL','testdir']));

    }

    function testPROPPATCH() {

        $this->assertTrue($this->server->emit('beforeMethod', ['PROPPATCH','testdir']));

    }

    function testCOPY() {

        $this->assertTrue($this->server->emit('beforeMethod', ['COPY','testdir']));

    }

    function testMOVE() {

        $this->assertTrue($this->server->emit('beforeMethod', ['MOVE','testdir']));

    }

    function testLOCK() {

        $this->assertTrue($this->server->emit('beforeMethod', ['LOCK','testdir']));

    }

    function testBeforeBind() {

        $this->assertTrue($this->server->emit('beforeBind', ['testdir/file']));

    }


    function testBeforeUnbind() {

        $this->assertTrue($this->server->emit('beforeUnbind', ['testdir']));

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

        $r = $this->server->emit('afterGetProperties', ['testdir',&$properties]);
        $this->assertTrue($r);

        $this->assertEquals($expected, $properties);

    }

}
