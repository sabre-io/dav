<?php

namespace Sabre\Common;

use PHPUnit_Framework_TestCase;

class ArrayCacheTest extends PHPUnit_Framework_TestCase {

    function testSetGet() {

        $cache = new ArrayCache();
        $cache->set('foo1', 'bar1');
        $cache->set('foo2', 'bar2');
        $cache->set('foo3', 'bar3');

        $result1 = $cache->get('foo1');
        $result2 = $cache->get('foo2');
        $result3 = $cache->get('foo3');
        $result4 = $cache->get('foo4');

        $this->assertSame('bar1', $result1);
        $this->assertSame('bar2', $result2);
        $this->assertSame('bar3', $result3);
        $this->assertNull($result4);

    }

    function testSetOverwritesPreviousValue() {

        $cache = new ArrayCache();
        $cache->set('foo1', 'bar1');
        $cache->set('foo1', 'bar2');

        $result = $cache->get('foo1');

        $this->assertSame('bar2', $result);

    }

    function testSetOverwritesPreviousValueWhenCapacityReached() {

        $cache = new ArrayCache(2);
        $cache->set('foo1', 'bar1');
        $cache->set('foo2', 'bar2');
        $cache->set('foo1', 'bar3');

        $result = $cache->get('foo1');

        $this->assertSame('bar3', $result);

    }

    function testRemove() {

        $cache = new ArrayCache();
        $cache->set('foo', 'bar1');
        $cache->set('foo', 'bar2');
        $cache->remove('foo');

        $result = $cache->get('foo');

        $this->assertNull($result);

    }

    function testKeyExists() {

        $cache = new ArrayCache();
        $cache->set('foo1', 'bar');
        $cache->set('foo2', 'bar');

        $this->assertTrue($cache->keyExists('foo1'));
        $this->assertTrue($cache->keyExists('foo2'));
        $this->assertFalse($cache->keyExists('foo3'));
    }

    function testKeyExistsReturnsFalseAfterRemoval() {

        $cache = new ArrayCache();
        $cache->set('foo', 'bar');
        $cache->remove('foo');

        $this->assertFalse($cache->keyExists('foo'));
    }

    function testGetAllWhenEmpty() {

        $cache = new ArrayCache();
        $this->assertCount(0, iterator_to_array($cache->getAll()));

    }

    function testGetAllWithData() {

        $cache = new ArrayCache();
        $cache->set('foo1', 'old_bar');
        $cache->set('foo1', 'new_bar');
        $cache->set('foo2', 'bar_bar');

        $result = iterator_to_array($cache->getAll());

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('foo1', $result);
        $this->assertArrayHasKey('foo2', $result);
        $this->assertSame('new_bar', $result['foo1']);
        $this->assertSame('bar_bar', $result['foo2']);

    }

    function testCacheSizeIsCapped() {

        $cache = new ArrayCache(2);
        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        $this->assertCount(2, iterator_to_array($cache->getAll()));

    }

}
