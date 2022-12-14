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

namespace Lightning\Http\Session;

use Traversable;
use ArrayIterator;
use IteratorAggregate;

/**
 * Flash component
 *
 * @internal values for keys should be a single flash message, not groups of messages.
 */
class Flash implements IteratorAggregate
{
    protected SessionInterface $session;

    /**
     * Constructor
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Sets a flash messages
     *
     * @param string $key
     * @param string $message
     * @return static
     */
    public function set(string $key, string $message): static
    {
        $flashed = $this->session->get('flash', []);

        $flashed[$key] = $message;
        $this->session->set('flash', $flashed);

        return $this;
    }

    /**
     * Checks if there is a flash message for the key
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool
    {
        $flashed = $this->session->get('flash', []);

        return isset($flashed[$key]);
    }

    /**
     * Gets the flash message and removes from the session
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        $flashed = $this->session->get('flash', []);

        $out = null;

        if (isset($flashed[$key])) {
            $out = $flashed[$key];
            unset($flashed[$key]);

            $this->session->set('flash', $flashed);
        }

        return $out;
    }

    /**
     * Gets all the flash messages and removes from session
     *
     * @return array
     */
    public function getMessages(): array
    {
        $messages = $this->session->get('flash', []);
        $this->session->set('flash', []);

        return $messages;
    }

    /**
     * IteratorAggregrate
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getMessages());
    }
}
