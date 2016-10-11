<?php

namespace Sabre\Common;

/**
 * Interface for a general purpose cache.
 */
interface Cache {

    /**
     * Retrieves an item from the cache.
     *
     * @param string $key
     * @return mixed|null
     */
    function get($key);

    /**
     * Saves an item to the cache.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    function set($key, $value);

    /**
     * Removes an item from the cache.
     *
     * @param string $key
     * @return void
     */
    function remove($key);

    /**
     * Checks whether an item is in the cache.
     *
     * @param string $key
     * @return bool
     */
    function keyExists($key);

    /**
     * Returns all items in the cache (key-value pairs).
     *
     * @return \Traversable
     */
    function getAll();

}
