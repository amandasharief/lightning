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

use LogicException;

/**
 * TODO: complete and migrate
 */
abstract class AbstractSession implements SessionInterface
{
    protected ?string $id = null;

    protected array $session = [];
    protected bool $isRegenerated = false;
    protected bool $isStarted = false;

    private function checkIsStarted(): void
    {
        if (! $this->isStarted) {
            throw new LogicException('Session must be started before it can be used');
        }
    }

    public function set(string $key, mixed $value): static
    {
        $this->checkIsStarted();
        $this->session[$key] = $value;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->checkIsStarted();

        return $this->session[$key] ?? $default;
    }

    public function unset(string $key): void
    {
        $this->checkIsStarted();
        unset($this->session[$key]);
    }

    public function has(string $key): bool
    {
        $this->checkIsStarted();

        return array_key_exists($key, $this->session);
    }

    public function clear(): void
    {
        $this->checkIsStarted();
        $this->session = [];
    }

    public function destroy(): void
    {
        $this->checkIsStarted();
        $this->session = [];
        $this->close();
        $this->id = null;
    }

    /**
     * Get the session ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Regenerates an ID
     *
     * @return boolean
     */
    public function regenerateId(): bool
    {
        $this->id = $this->createId();
        $this->isRegenerated = true;

        return true;
    }

    /**
     * Checks if the Session is active
     *
     * @return boolean
     */
    public function isStarted(): bool
    {
        return $this->isStarted;
    }

    /**
     * Generates a session ID compatible for this storage
     *
     * @return string
     */
    protected function createId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
