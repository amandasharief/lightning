<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2024 Amanda Sharief
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\DataMapper\DataSource;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Lightning\QueryBuilder\QueryBuilder;
use Lightning\DataMapper\Exception\DataSourceException;

class DatabaseDataSource implements DataSourceInterface
{
    protected PDO $pdo;
    protected QueryBuilder $builder;
    private ?LoggerInterface $logger = null;

    /**
     * Mixed
     *
     * @var string|int|null
     */
    protected $id = null;

    /**
     * Constructor
    */
    public function __construct(PDO $pdo, QueryBuilder $builder)
    {
        $this->pdo = $pdo;
        $this->builder = $builder;
    }

    /**
     * @return string|int|null
     */
    public function getGeneratedId()
    {
        return $this->id;
    }

    /**
     * Creates a record in the database
     */
    public function create(string $table, array $data): bool
    {
        $builder = $this->builder
            ->insert(array_keys($data))
            ->into($table)
            ->values(array_values($data));

        $result = $this->execute($builder->toString(), $builder->getParameters())->rowCount() === 1;

        if ($result) {
            $id = $this->pdo->lastInsertId(); // Don't pass table Wrong object type: 7 ERROR:  "articles" is not a sequence
            $this->id = ctype_digit($id) ? (int) $id : $id;
        }

        return $result;
    }

    /**
     * Reads from the DataSource
     */
    public function read(string $table, array $query = []): array
    {
        $builder = $this->builder
            ->select(empty($query['fields']) ? ['*'] : $query['fields'])
            ->from($table);
        if ($query['criteria'] ?? []) {
            $builder->where($query['criteria']);
        }
        $this->applyOptions($builder, $query);

        return $this->execute($builder->toString(), $builder->getParameters())->fetchAll();
    }

    /**
     * Updates a record in the DataSource
     * @param array $query
     *   - criteria an array of sql like conditions e.g ['id'=>1000]
     */
    public function update(string $table, array $data, array $query = []): int
    {
        $builder = $this->builder->update($table)->set($data);
        if ($query['criteria'] ?? []) {
            $builder->where($query['criteria']);
        }
        $this->applyOptions($builder, $query);

        return $this->execute($builder->toString(), $builder->getParameters())->rowCount();
    }

    /**
     * Deletes records from the Datasource
     * @param array $query
    *   - criteria an array of sql like conditions e.g ['id'=>1000]
    */
    public function delete(string $table, array $query = []): int
    {
        $builder = $this->builder->delete()->from($table);
        if ($query['criteria'] ?? []) {
            $builder->where($query['criteria']);
        }
        $this->applyOptions($builder, $query);

        return $this->execute($builder->toString(), $builder->getParameters())->rowCount();
    }

    /**
     * Counts the number of records in the database (DOES NOT SUPPORT GROUP)
     */
    public function count(string $table, array $query = []): int
    {
        $fields = ['COUNT(*) as count'];

        $builder = $this->builder->select($fields)->from($table);
        if ($query['criteria'] ?? []) {
            $builder->where($query['criteria']);
        }

        $this->applyOptions($builder, $query);

        return (int) $this->execute($builder->toString(), $builder->getParameters())->fetchColumn(0);
    }

    /**
    * Runs a SELECT query on the database
    */
    public function query(string $sql, array $params = [], int $mode = PDO::FETCH_ASSOC): array
    {
        return $this->execute($sql, $params)->fetchAll($mode);
    }

    /**
     * Execute raw queries on the data source
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        if ($this->logger) {
            $this->logger->log(LogLevel::DEBUG, $this->prepareStatement($sql, $params));
        }

        try {
            if ($statement = $this->pdo->prepare($sql)) {
                $statement->execute($params);
                return $statement;
            }

            throw new PDOException($this->pdo->errorInfo()[2] ?? sprintf('ERROR Executing: %s', $sql));
        } catch(PDOException $e) {
            if ($this->logger) {
                $this->logger->log(LogLevel::ERROR, $e->getMessage());
            }

            throw new DataSourceException($e->getMessage());
        }
    }

    /**
     * unprepares an SQL statement
     */
    private function prepareStatement(string $sql, array $params = []): string
    {
        $data = [];
        foreach ($params as $needle => $replace) {
            if (is_string($replace)) {
                $replace = "'{$replace}'";
            }
            if ($replace === null) {
                $replace = 'NULL';
            }
            $data[$needle] = $replace;
        }

        return strtr($sql, $data);
    }

    private function applyOptions(QueryBuilder $builder, array $options): void
    {
        $joins = $options['joins'] ?? [];

        foreach ($joins as $join) {
            if (! isset($join['table'])) {
                throw new InvalidArgumentException(sprintf('Join configuration array is missing `table`'));
            }
            $type = strtolower($join['type'] ?? 'LEFT');

            if (! in_array($type, ['left','right','full','inner'])) {
                throw new InvalidArgumentException(sprintf('Invalid join type `%s`', $type));
            }
            $method = $type . 'Join';
            $builder->$method($join['table'], $join['alias'] ?? null, $join['conditions'] ?? []);
        }

        if (! empty($options['group'])) {
            $builder->groupBy($options['group']);
        }

        if (! empty($options['having'])) {
            $builder->having($options['having']);
        }

        if (! empty($options['order'])) {
            $builder->orderBy($options['order']);
        }

        if (! empty($options['limit'])) {
            $builder->limit($options['limit'], $options['offset'] ?? null);
        }
    }

    /**
     * Sets the PSR-3 logger and enables query logging
     */
    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Gets the PSR-3 logger if set
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->builder;
    }
}
