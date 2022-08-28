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

namespace Lightning\Http\Auth\IdentityService;

use PDO;
use RuntimeException;
use Lightning\Http\Auth\Identity;
use Lightning\Http\Auth\IdentityServiceInterface;

class PdoIdentityService implements IdentityServiceInterface
{
    private PDO $pdo;
    private string $table = 'users';
    private string $identifierName = 'email';
    private string $credentialName = 'password';
    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Gets the identifier name to be used e.g. username, email, token, etc
     *
     * @return string
     */
    public function getIdentifierName(): string
    {
        return $this->identifierName;
    }

    /**
     * Sets the Table
     *
     * @param string $table
     * @return static
     */
    public function setTable(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Sets the identifier name
     *
     * @param string $name
     * @return static
     */
    public function setIdentifierName(string $name): static
    {
        $this->identifierName = $name;

        return $this;
    }

    /**
     * Returns a new storage object with a different name for the identifier
     *
     * @param string $name
     * @return static
     */
    public function withIdentifierName(string $name): static
    {
        return (clone $this)->setIdentifierName($name);
    }

    /**
     * Finds the user by the identifier
     *
     * @param string $identifier username, email, token etc.
     * @return Identity|null
     */
    public function findByIdentifier(string $identifier): ?Identity
    {
        $statement = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->identifierName} = ?");
        if (! $statement->execute([$identifier])) {
            throw new RuntimeException('Error executing SQL statement');
        }

        $user = $statement->fetch();

        return $user ? new Identity($user) : null;
    }

    /**
     * Get the value of credential name
     */
    public function getCredentialName(): string
    {
        return $this->credentialName;
    }

    /**
     * Set the value of credential name
     */
    public function setCredentialName(string $credentialName): static
    {
        $this->credentialName = $credentialName;

        return $this;
    }
}
