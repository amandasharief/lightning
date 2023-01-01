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

use Lightning\Cache\Exception\InvalidArgumentException;

/**
 * Null Cache
 */
class NullCache extends AbstractCache
{

    public function get($key, $default = null)
    {
        return $default;
    }


    public function set($key, $value, $ttl = null)
    {
        return true;
    }


    public function has($key)
    {
        return false;
    }

    public function delete($key)
    {
        return false;
    }

    public function clear()
    {
          return true;
    }
}
