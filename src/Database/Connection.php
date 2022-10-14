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

namespace Lightning\Database;

use PDO;
use Throwable;
use Stringable;
use Psr\Log\LoggerInterface;
use Lightning\Database\Exception\DatabaseException;

/**
 * Connection
 * Database abstraction-level (DBAL) which adds support for logging, caching and other stuff.
 */
class Connection
{
    protected ?PDO $pdo = null;
    protected ?LoggerInterface $logger = null;

    private const DEFAULT_PDO_OPTIONS = [
        /**
         * don't set to true unless you know what you are doing, this can have all kinds of effects
         * that need to be understood properly.
         */
        PDO::ATTR_PERSISTENT => false,
        /**
         * 1. This must be set to false for security reasons
         * 2. It also plays a part in cast in casting data types such as integer
         */
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    /**
     * Constructor
     */
    public function __construct(
        private string $dsn,
        private ?string $username = null,
        private ?string $password = null,
        private array $pdoOptions = self::DEFAULT_PDO_OPTIONS
    ) {
    }

    /**
     * Creates the DB connection
     */
    public function connect(): void
    {
        if (! isset($this->pdo)) {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->pdoOptions);
        }
    }

    /**
     * Disconnects from the DB
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * Checks if connected
     */
    public function isConnected(): bool
    {
        return $this->pdo instanceof PDO;
    }

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Gets the PDO object
     */
    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Gets the DSN string
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }

    /**
     * Begins a Transaction
     */
    public function beginTransaction(): bool
    {
        if ($this->inTransaction()) {
            return false;
        }

        if ($this->logger) {
            $this->logger->debug('BEGIN');
        }

        return $this->pdo->beginTransaction();
    }

    /**
     * Commits a Transaction
     */
    public function commit(): bool
    {
        if (! $this->inTransaction()) {
            return false;
        }

        if ($this->logger) {
            $this->logger->debug('COMMIT');
        }

        return $this->pdo->commit();
    }

    /**
     * Rollsback a Transaction
     */
    public function rollback(): bool
    {
        if (! $this->inTransaction()) {
            return false;
        }

        if ($this->logger) {
            $this->logger->debug('ROLLBACK');
        }

        return $this->pdo->rollBack();
    }

    /**
     * Checks to see if currently in a transaction
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Prepares an SQL statement
     */
    public function prepare(string|Stringable $query): Statement
    {
        $sql = $query instanceof Stringable ? (string) $query : $query;

        $statement = $this->pdo->prepare($sql);

        // @codeCoverageIgnoreStart
        if ($statement === false) {
            throw new DatabaseException(sprintf('Error preparing query `%s`', $sql)); // can't get here
        }
        // @codeCoverageIgnoreEnd

        return new Statement($statement);
    }

    /**
     * Executes a statement and returns a decorated PDO statement
     */
    public function execute(string|Stringable $query, array $params = []): Statement
    {
        $statement = $this->prepare($query);
        if ($params) {
            $statement->bind($params);
        }
        $statement->execute();

        if ($this->logger) {
            $this->logger->debug($this->interpolateStatement($statement->getQueryString(), $params));
        }

        return $statement;
    }

    /**
     * A helper method to execute transactional SQL queries with automatic rollback if the
     * callable throws an exception or returns false.
     */
    public function transaction(callable $callable): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callable($this);
        } catch (Throwable $exception) {
            $this->rollback();

            throw $exception;
        }

        if ($result === false) {
            $this->rollback();
        } else {
            $this->commit();
        }

        return $result;
    }

    /**
     * Gets the last insert ID
     *
     * @see https://www.php.net/manual/en/pdo.lastinsertid.php
     */
    public function getLastInsertId(?string $sequence = null): ?string
    {
        $id = $this->pdo->lastInsertId($sequence);

        return ! is_string($id) || $id === '0' ? null : $id;
    }

    /**
     * A simple interpolater for logging purposes
     */
    private function interpolateStatement(string $sql, array $params): string
    {
        $keys = [];
        $values = [];
        foreach ($params as $key => $value) {
            $keys[] = is_int($key) ? '/\?/' : '/:' . $key .'/';
            $values[] = $value;
        }

        return preg_replace($keys, $values, $sql, 1);
    }

    /**
     * Inserts a row into the table
     *
     * @example INSERT INTO tags (name,created_at,updated_at) VALUES (?,?,?)
     */
    public function insert(string $table, array $data): bool
    {
        $set = array_keys($data);
        $values = array_fill(0, count($data), '?');

        $query = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(',', $set), implode(',', $values));

        return $this->execute($query, array_values($data))->rowCount() === 1;
    }

    /**
     * Updates a row or rows in the database
     *
     * @example UPDATE tags SET name = ?, created_at = ?, updated_at = ? WHERE id = ?
     */
    public function update(string $table, array $data, array $identifiers = []): int
    {
        $set = $this->toPlaceholders($data);
        $values = array_merge(array_values($data), array_values($identifiers));

        $query = sprintf('UPDATE %s SET %s', $table, implode(', ', $set));
        if ($identifiers) {
            $query .= sprintf(' WHERE %s', implode(' AND ', $this->toPlaceholders($identifiers)));
        }

        return $this->execute($query, $values)->rowCount();
    }

    /**
     * Deletes a row or rows from the database
     *
     * @example DELETE FROM articles WHERE id = ?
     */
    public function delete(string $table, array $identifiers = []): int
    {
        $query = sprintf('DELETE FROM %s', $table);
        if ($identifiers) {
            $query .= sprintf(' WHERE %s', implode(' AND ', $this->toPlaceholders($identifiers)));
        }

        return $this->execute($query, array_values($identifiers))->rowCount();
    }

    /**
     * Convert an array of data into a placeholder set
     */
    private function toPlaceholders(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $key . ' = ?';
        }

        return $result;
    }

    /**
     * Gets the Driver name for this connection
     */
    public function getDriver(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
