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

namespace Lightning\Http\Auth\PasswordHasher;

use InvalidArgumentException;
use Lightning\Http\Auth\PasswordHasherInterface;

/**
 * BcryptPasswordHasher
 *
 * @see https://www.php.net/manual/en/function.password-hash.php
 */
class BcryptPasswordHasher implements PasswordHasherInterface
{
    /**
     * Hashes the password
     *
     * @param string $pasword
     * @return string
     * @throws InvalidArgumentException
     */
    public function hash(string $pasword): string
    {
        $this->validatePassword($pasword);

        return password_hash($pasword, PASSWORD_BCRYPT);
    }

    /**
     * Verifies the password against the hashed password
     *
     * @param string $password
     * @param string $hash
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function verify(string $password, string $hash): bool
    {
        $this->validatePassword($password);

        return password_verify($password, $hash);
    }

    /**
     * Check the password is not empty or exceeds what will be used by the pasword hashing.
     * BCRYPT truncates passwords after 72 bytes
     *
     * @param string $password
     * @return void
     * @throws InvalidArgumentException
     */
    private function validatePassword(string $password): void
    {
        if ($password === '' || strlen($password) > 72) {
            throw new InvalidArgumentException('Invalid password');
        }
    }
}
