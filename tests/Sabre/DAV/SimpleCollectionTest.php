<?php

namespace Sabre\DAV;

class SimpleCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructNode()
    {
        $s = new SimpleCollection('foo', [new SimpleFile('bar.txt', 'hi')]);
        self::assertEquals('foo', $s->getName());
        self::assertEquals('bar.txt', $s->getChild('bar.txt')->getName());
    }

    public function testConstructNodeArray()
    {
        $s = new SimpleCollection('foo', [
            'bar' => [],
            'baz.txt' => 'hi',
            'gir' => [
                'zim' => 'world',
            ],
        ]);
        self::assertEquals('foo', $s->getName());
        self::assertEquals('bar', $s->getChild('bar')->getName());
        self::assertEquals('hi', $s->getChild('baz.txt')->get());
        self::assertEquals('world', $s->getChild('gir')->getChild('zim')->get());
    }

    public function testConstructBadParam()
    {
        $this->expectException('InvalidArgumentException');
        new SimpleCollection('foo', [new \stdClass()]);
    }

    public function testGetChildren()
    {
        $child = new SimpleFile('bar.txt', 'hi');

        $s = new SimpleCollection('foo', [$child]);
        self::assertEquals([$child], $s->getChildren());
    }

    public function testGetChild404()
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);
        $s = new SimpleCollection('foo', []);
        $s->getChild('404');
    }
}
