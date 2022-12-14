<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2022 Amanda Sharief.
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\Cache;

use Redis;
use Lightning\Cache\Exception\InvalidArgumentException;

class RedisCache extends AbstractCache
{
    private Redis $redis;

    /**
     * Constructor
     */
    public function __construct(Redis $redis, string $prefix = '', int $timeout = 0)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->timeout = $timeout;
    }

    /**
    * Fetches a value from the cache.
    *
    * @param string $key     The unique key of this item in the cache.
    * @param mixed  $default Default value to return if the key does not exist.
    * @return mixed The value of the item from the cache, or $default in case of cache miss.
    * @throws InvalidArgumentException
    *   MUST be thrown if the $key string is not a legal value.
    */
    public function get($key, $default = null)
    {
        $key = $this->addPrefix($key);
        $result = $this->redis->get($key);
        if ($result === false) {
            return $default;
        }

        return ctype_digit($result) ? (int) $result : unserialize($result);
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                 $key   The key of the item to store.
     * @param mixed                  $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     * @throws InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null)
    {
        $key = $this->addPrefix($key);
        $ttl = $this->getDuration($ttl);

        if (! is_int($value)) {
            $value = serialize($value);
        }

        return $ttl === 0 ? $this->redis->set($key, $value) : $this->redis->setex($key, $ttl, $value);
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     * @return bool
     * @throws InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key)
    {
        $key = $this->addPrefix($key);

        return $this->redis->get($key) !== false;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     * @return bool True if the item was successfully removed. False if there was an error.
     * @throws InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key)
    {
        $key = $this->addPrefix($key);

        return $this->redis->del($key) > 0;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        $result = true;
        foreach ($this->redis->keys($this->prefix . '*') as $key) {
            $result = $result && $this->redis->del($key);
        }

        return $result;
    }

    /**
     * Increments the value of an integer in the cache and updates the expiry time
     *
     * @param string $key
     * @param integer $offset
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     * @return integer
     */
    public function increment(string $key, $offset = 1, $ttl = null): int
    {
        $key = $this->addPrefix($key);
        $ttl = $this->getDuration($ttl);

        $result = $this->redis->incrBy($key, $offset);

        if ($ttl) {
            $this->redis->expire($key, $ttl);
        }

        return $result;
    }

    /**
     * Decrements the value of an integer in the cache and updates the expiry time
     *
     * @param string $key
     * @param integer $offset
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     * @return integer
     */
    public function decrement(string $key, $offset = 1, $ttl = null): int
    {
        $key = $this->addPrefix($key);
        $ttl = $this->getDuration($ttl);

        $result = $this->redis->decrBy($key, $offset);

        if ($ttl) {
            $this->redis->expire($key, $ttl);
        }

        return $result;
    }
}
