<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;

class PluginUpdatePropertiesTest extends \PHPUnit\Framework\TestCase
{
    public function testUpdatePropertiesPassthrough()
    {
        $tree = [
            new DAV\SimpleCollection('foo'),
        ];
        $server = new DAV\Server($tree);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', [
            '{DAV:}foo' => 'bar',
        ]);

        $expected = [
            '{DAV:}foo' => 403,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testRemoveGroupMembers()
    {
        $tree = [
            new MockPrincipal('foo', 'foo'),
        ];
        $server = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($plugin);

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => null,
        ]);

        $expected = [
            '{DAV:}group-member-set' => 204,
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals([], $tree[0]->getGroupMemberSet());
    }

    public function testSetGroupMembers()
    {
        $tree = [
            new MockPrincipal('foo', 'foo'),
        ];
        $server = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($plugin);

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => new DAV\Xml\Property\Href(['/bar', '/baz']),
        ]);

        $expected = [
            '{DAV:}group-member-set' => 200,
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals(['bar', 'baz'], $tree[0]->getGroupMemberSet());
    }

    public function testSetBadValue()
    {
        $this->expectException('Sabre\DAV\Exception');
        $tree = [
            new MockPrincipal('foo', 'foo'),
        ];
        $server = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($plugin);

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => new \stdClass(),
        ]);
    }

    public function testSetBadNode()
    {
        $tree = [
            new DAV\SimpleCollection('foo'),
        ];
        $server = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($plugin);

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => new DAV\Xml\Property\Href(['/bar', '/baz']),
        ]);

        $expected = [
            '{DAV:}group-member-set' => 403,
        ];

        $this->assertEquals($expected, $result);
    }
}
