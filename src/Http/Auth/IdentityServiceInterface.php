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
 * Attach this to your models etc to use with  your existing setups.
 */
interface IdentityServiceInterface
{
    /**
     * Get the identifier name e.g. username, email, token etc
     */
    public function getIdentifierName(): string;

    /**
     * Gets the credential name e.g. password, hashed_password
     */
    public function getCredentialName(): string;

    /**
     * Finds the user details by the provided identifier
     *
     * @param string $identifier    username, email, token etc
     */
    public function findByIdentifier(string $identifier): ?Identity;
}
