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

namespace Lightning\TestSuite;

/**
 * Test session using default PHP sessions, if you are not using PHP sessions and using a custom
 * class then create your own test session using the TestSessionInterface.
 */
class TestSession implements TestSessionInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $_SESSION = []; // Create session global in CLI
    }

    /**
     * Sets a value in the Session
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Checks if the session has a value
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Gets an item from the Session
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Clears the Session
     *
     * @return void
     */
    public function clear(): void
    {
        $_SESSION = [];
    }
}
