<?php

declare(strict_types=1);

namespace Sabre\DAV;

class ServerUpdatePropertiesTest extends \PHPUnit\Framework\TestCase
{
    public function testUpdatePropertiesFail()
    {
        $tree = [
            new SimpleCollection('foo'),
        ];
        $server = new Server($tree);

        $result = $server->updateProperties('foo', [
            '{DAV:}foo' => 'bar',
        ]);

        $expected = [
            '{DAV:}foo' => 403,
        ];
        self::assertEquals($expected, $result);
    }

    public function testUpdatePropertiesProtected()
    {
        $tree = [
            new SimpleCollection('foo'),
        ];
        $server = new Server($tree);

        $server->on('propPatch', function ($path, PropPatch $propPatch) {
            $propPatch->handleRemaining(function () { return true; });
        });
        $result = $server->updateProperties('foo', [
            '{DAV:}getetag' => 'bla',
            '{DAV:}foo' => 'bar',
        ]);

        $expected = [
            '{DAV:}getetag' => 403,
            '{DAV:}foo' => 424,
        ];
        self::assertEquals($expected, $result);
    }

    public function testUpdatePropertiesEventFail()
    {
        $tree = [
            new SimpleCollection('foo'),
        ];
        $server = new Server($tree);
        $server->on('propPatch', function ($path, PropPatch $propPatch) {
            $propPatch->setResultCode('{DAV:}foo', 404);
            $propPatch->handleRemaining(function () { return true; });
        });

        $result = $server->updateProperties('foo', [
            '{DAV:}foo' => 'bar',
            '{DAV:}foo2' => 'bla',
        ]);

        $expected = [
            '{DAV:}foo' => 404,
            '{DAV:}foo2' => 424,
        ];
        self::assertEquals($expected, $result);
    }

    public function testUpdatePropertiesEventSuccess()
    {
        $tree = [
            new SimpleCollection('foo'),
        ];
        $server = new Server($tree);
        $server->on('propPatch', function ($path, PropPatch $propPatch) {
            $propPatch->handle(['{DAV:}foo', '{DAV:}foo2'], function () {
                return [
                    '{DAV:}foo' => 200,
                    '{DAV:}foo2' => 201,
                ];
            });
        });

        $result = $server->updateProperties('foo', [
            '{DAV:}foo' => 'bar',
            '{DAV:}foo2' => 'bla',
        ]);

        $expected = [
            '{DAV:}foo' => 200,
            '{DAV:}foo2' => 201,
        ];
        self::assertEquals($expected, $result);
    }
}
