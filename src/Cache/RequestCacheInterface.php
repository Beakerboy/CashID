<?php

namespace CashID\Cache;

/**
 * Request Cache Interface
 *
 * An interface that defines the means of interacting with a key value cache.
 * The cache is used to store CashID requests that have been send by the
  * server, with the nonce as the unique key.
 */
interface RequestCacheInterface
{
    /**
     * Store
     *
     * Store a key / value pair in the cache
     *
     * @param string $key
     * @param mixed $var
     */
    public function store(string $key, $var);

    /**
     * Fetch
     *
     * Fetch the value of a key from the cache
     *
     * @param string $key
     */
    public function fetch(string $key);

    /**
     * Exists
     *
     * Check if a key exists in the cache
     *
     * @param string $key
     */
    public function exists(string $key);
}
