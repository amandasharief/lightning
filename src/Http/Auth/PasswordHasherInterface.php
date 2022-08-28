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

namespace Lightning\Http\Auth;

/**
 * @see https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html
 */
interface PasswordHasherInterface
{
    /**
     * Hashes a password
     *
     * @param string $pasword
     * @return string
     */
    public function hash(string $pasword): string;

    /**
     * Checks a plain text password againt the hashed version
     *
     * @param string $password
     * @param string $hashedPassword
     * @return boolean
     */
    public function verify(string $password, string $hashedPassword): bool;
}
