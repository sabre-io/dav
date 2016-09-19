<?php

namespace Sabre\Common;

use ArrayIterator;

/**
 * Implementation of Cache interface using a simple associative array.
 */
class ArrayCache implements Cache {

    const DEFAULT_CACHE_SIZE = 1000;

    /** @var int Maximum count of items in the cache */
    private $cacheSize;

    /** @var mixed[] */
    private $cache = [];

    /**
     * @param int $cacheSize
     */
    function __construct($cacheSize = self::DEFAULT_CACHE_SIZE) {

        $this->cacheSize = $cacheSize;

    }

    /**
     * Retrieves an item from the cache.
     *
     * @param string $key
     * @return mixed|null
     */
    function get($key) {

        return array_key_exists($key, $this->cache) ? $this->cache[$key] : null;

    }

    /**
     * Saves an item to the cache.
     *
     * When the capacity is reached, no new items will be added. Existing items under will still get updated even if
     * the capacity is reached.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    function set($key, $value) {

        $addingNewItem = !array_key_exists($key, $this->cache);
        if ($addingNewItem && count($this->cache) >= $this->cacheSize) {
            // don't store new values, if the cache is full
            return;
        }
        $this->cache[$key] = $value;

    }

    /**
     * Removes an item from the cache.
     *
     * @param string $key
     * @return void
     */
    function remove($key) {

        unset($this->cache[$key]);

    }

    /**
     * Checks whether an item is in the cache.
     *
     * @param string $key
     * @return bool
     */
    function keyExists($key) {

        return array_key_exists($key, $this->cache);

    }

    /**
     * Returns all items in the cache (key-value pairs).
     *
     * @return \Traversable
     */
    function getAll() {

        return new ArrayIterator($this->cache);

    }

}
