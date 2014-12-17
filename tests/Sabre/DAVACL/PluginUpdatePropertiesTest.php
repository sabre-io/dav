<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;


require_once 'Sabre/DAVACL/MockPrincipal.php';

class PluginUpdatePropertiesTest extends \PHPUnit_Framework_TestCase {

    function testUpdatePropertiesPassthrough() {

        $tree = array(
            new DAV\SimpleCollection('foo'),
        );
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', array(
            '{DAV:}foo' => 'bar',
        ));

        $expected = array(
            '{DAV:}foo' => 403,
        );

        $this->assertEquals($expected, $result);

    }

    function testRemoveGroupMembers() {

        $tree = array(
            new MockPrincipal('foo','foo'),
        );
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', array(
            '{DAV:}group-member-set' => null,
        ));

        $expected = array(
            '{DAV:}group-member-set' => 204
        );

        $this->assertEquals($expected, $result);
        $this->assertEquals(array(),$tree[0]->getGroupMemberSet());

    }

    function testSetGroupMembers() {

        $tree = [
            new MockPrincipal('foo','foo'),
        ];
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => new DAV\Xml\Property\Href(['/bar','/baz'], true),
        ]);

        $expected = [
            '{DAV:}group-member-set' => 200
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals(['bar', 'baz'],$tree[0]->getGroupMemberSet());

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testSetBadValue() {

        $tree = array(
            new MockPrincipal('foo','foo'),
        );
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', array(
            '{DAV:}group-member-set' => new \StdClass(),
        ));

    }

    function testSetBadNode() {

        $tree = [
            new DAV\SimpleCollection('foo'),
        ];
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => new DAV\Xml\Property\Href(['/bar','/baz'],false),
        ]);

        $expected = [
            '{DAV:}group-member-set' => 403,
        ];

        $this->assertEquals($expected, $result);

    }
}
