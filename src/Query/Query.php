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

namespace Lightning\Query;

use PDO;
use PDOStatement;
use ArrayIterator;
use RuntimeException;
use IteratorAggregate;

use BadMethodCallException;
use Lightning\Database\Row;
use Lightning\QueryBuilder\QueryBuilder;

/**
 * Query
 *
 * @internal remove the aggregate functions like count, since this requires changing this to another layer
 * of arrays and then processing, its just not worth extra code
 *
 * naming thoughts With regards to getting query  getResult and getSingleResult inline with other libraries.
 */
class Query implements IteratorAggregate
{
    protected PDO $pdo;
    protected QueryBuilder $queryBuilder;

    private ?string $table = null;

    /**
     * Prepares for insert
     *
     * @var array
     */
    private array $insert = [];

    private array $selectColumns = [];

    private bool $selectIsWildcard = true;
    private string $driver;

    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo, QueryBuilder $queryBuilder)
    {
        $this->pdo = $pdo;
        $this->driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Selects columns
     *
     * @param string|array $columns
     * @return static
     */
    public function select(string|array $columns): static
    {
        $this->selectColumns = (array) $columns;
        $this->selectIsWildcard = in_array('*', $this->selectColumns);

        $this->queryBuilder = $this->queryBuilder->select($this->selectColumns);

        $this->table = null;

        return $this;
    }

    /**
     * Handles the from type
     *
     * @param string $table
     * @param string|null $alias
     * @return static
     */
    public function from(string $table, string $alias = null): static
    {
        $this->queryBuilder->from($table, $alias);
        $this->table = $alias ?: $table;

        return $this;
    }

    /**
     * The WHERE clause, if you use multiple strings they will be joined together with an AND
     *
     * @param array|string $expression
     * @return static
     */
    public function where(string|array $expression): static
    {
        $this->queryBuilder->where($expression);

        return $this;
    }

    /**
     * @param integer $limit
     * @param integer|null $offset
     * @return static
     */
    public function limit(int $limit, int $offset = null): static
    {
        $this->queryBuilder->limit($limit, $offset);

        return $this;
    }

    /**
     * @param string|array $group field
     * @return static
     */
    public function groupBy(string|array $group): static
    {
        $this->queryBuilder->groupBy($group);

        return $this;
    }

    /**
     * @param string|array $order 'id ASC' or ['id','name DESC']
     * @return static
     */
    public function orderBy(string|array $order): static
    {
        $this->queryBuilder->orderBy($order);

        return $this;
    }

    /**
     * @param string $table e.g. customers
     * @param string|null $alias
     * @param string|array $condition orders.customer_id = customers.id
     * @return static
     */
    public function innerJoin(string $table, ?string $alias = null, string|array $condition = []): static
    {
        $this->checkDriverSupportsTableAliasMeta($alias);
        $this->queryBuilder->innerJoin($table, $alias, $condition);

        return $this;
    }

    /**
     * @param string $table e.g. customers
     * @param string|null $alias
     * @param string|array $condition orders.customer_id = customers.id
     * @return static
     */
    public function leftJoin(string $table, ?string $alias = null, string|array $condition = []): static
    {
        $this->checkDriverSupportsTableAliasMeta($alias);
        $this->queryBuilder->leftJoin($table, $alias, $condition);

        return $this;
    }

    /**
     * @param string $table e.g. customers
     * @param string|null $alias
     * @param string|array $condition orders.customer_id = customers.id
     * @return static
     */
    public function rightJoin(string $table, ?string $alias = null, string|array $condition = []): static
    {
        $this->checkDriverSupportsTableAliasMeta($alias);
        $this->queryBuilder->rightJoin($table, $alias, $condition);

        return $this;
    }

    /**
     * @param string $table e.g. customers
     * @param string|null $alias
     * @param string|array $condition orders.customer_id = customers.id
     * @return static
     */
    public function fullJoin(string $table, ?string $alias = null, string|array $condition = []): static
    {
        $this->checkDriverSupportsTableAliasMeta($alias);
        $this->queryBuilder->fullJoin($table, $alias, $condition);

        return $this;
    }

    /**
    * @param string|array $having e.g. COUNT(id) > 3
    * @return static
    */
    public function having(string|array $having): static
    {
        $this->queryBuilder->having($having);

        return $this;
    }

    /**
     * Paging function (uses limit and offset under the hood).
     *
     * @param integer $page
     * @param integer $items
     * @return static
     */
    public function page(int $page, int $items = 20): static
    {
        $this->queryBuilder->limit($items, ($page * $items) - $items);

        return $this;
    }

    /**
     * Gets the first record that matches
     *
     * @return Row|null
     */
    public function get(): ?Row
    {
        $statement = $this->run();

        $row = $statement->fetch(PDO::FETCH_NUM);

        return $row ? $this->mapRow($row, $this->getColumnMeta($statement)) : null;
    }

    /**
     * @internal getColumnMeta for both postgres and sqlite returns the table name not the alias, therefore a
     * custom getColumnMeta needs to be implemented, this only works if columns are provided to the select method.
     * Therefore using postgres/sqlite with aliased joins wont work if you dont supply the field names.
     *
     * @param PDOStatement $statement
     * @return array
     */
    private function getColumnMeta(PDOStatement $statement): array
    {
        $result = [];
        if ($this->selectIsWildcard) {
            $max = $statement->columnCount();
            for ($column = 0;$column < $max;$column++) {
                $meta = $statement->getColumnMeta($column);
                $result[] = ($meta['table'] ?? null) . '.' . $meta['name'];
            }
        } else {
            foreach ($this->selectColumns as $column) {
                $alias = null;
                if (preg_match('/^[A-Za-z0-9_]+\.[a-z0-9_]+$/i', $column)) {
                    list($alias, $column) = explode('.', $column);
                } elseif ($position = stripos($column, ' AS ')) {
                    if ($position !== false) {
                        $column = substr($column, $position + 4);
                    }
                }
                $result[] = $alias . '.' . $column;
            }
        }

        return $result;
    }

    /**
     * Doing wildcard queries on drivers
     *
     * @param string|null $alias
     * @return void
     */
    private function checkDriverSupportsTableAliasMeta(?string $alias): void
    {
        if ($this->selectIsWildcard && $alias && in_array($this->driver, ['pgsql','sqlite'])) {
            throw new BadMethodCallException('You must provide the column names for this select query');
        }
    }

    /**
     * Gets all the records that match
     *
     */
    public function all(): array
    {
        $statement = $this->run();
        $meta = $this->getColumnMeta($statement);

        return array_map(function ($row) use ($meta) {
            return $this->mapRow($row, $meta);
        }, $statement->fetchAll(PDO::FETCH_NUM) ?: []);
    }

    /**
     * Create an Insert Into Query
     *
     * @internal I want the insert method to work a bit differently and this requires to not call the query builder
     * directly
     *
     * @param string $table
     * @param array $columns
     * @return static
     */
    public function insertInto(string $table, array $columns = []): static
    {
        $this->insert = [$table,$columns];

        return $this;
    }

    /**
     * Sets the values for an insert query
     *
     * @param array $data
     * @return static
     */
    public function values(array $data): static
    {
        if (! $this->insert) {
            throw new BadMethodCallException('InsertInto must be called first');
        }

        list($table, $columns) = $this->insert;

        if (empty($columns)) {
            $columns = array_keys($data);
        }

        $this->queryBuilder
            ->insert($columns)
            ->into($table)
            ->values(array_values($data));

        $this->insert = [];

        return $this;
    }

    /**
     * Creates an Update query
     *
     * @param string $table
     * @return static
     */
    public function update(string $table): static
    {
        $this->queryBuilder->update($table);

        return $this;
    }

    /**
     * Sets the fields to be updated by the Update query
     *
     * @param array $data
     * @return static
     */
    public function set(array $data): static
    {
        $this->queryBuilder->set($data);

        return $this;
    }

    /**
     * Creates a Delete From query
     *
     * @return static
     */
    public function deleteFrom(string $table): static
    {
        $this->queryBuilder->delete()->from($table);

        return $this;
    }

    /**
     * Converts to result to an aggregate object
     *
     * @internal If there is a need in future, the table grouping feature can be enabled/disabled etc.
     *
     * @param array $data
     * @param array $meta
     * @return Row
     */
    private function mapRow(array $data, array $meta): Row
    {
        $row = new Row();

        $data = array_combine($meta, $data);

        foreach ($data as $field => $value) {
            $table = null;

            if (strpos($field, '.') && strpos($field, '(') === false && strpos($field, ' ') === false) {
                list($table, $field) = explode('.', $field);

                if ($table !== $this->table) {
                    if (! isset($row->$table)) {
                        $row->$table = new Row();
                    }
                    $row->$table->$field = $value;

                    continue;
                }
            }
            // work with AS which SELECT COUNT(*) AS count returns `.count`
            $field = $field[0] === '.' ? substr($field, 1) : $field;

            $row->$field = $value;
        }

        return $row;
    }

    /**
     * @return PDOStatement
     */
    private function run(): PDOStatement
    {
        $statement = $this->pdo->prepare($this->queryBuilder->toString());
        if (! $statement->execute($this->queryBuilder->getParameters())) {
            throw new RuntimeException(sprintf('Error executing SQL `%s`', $this->queryBuilder->toString()));
        }

        return $statement;
    }

    /**
     * Executes a Insert,Update or Delete Query
     *
     * @return integer
     */
    public function execute(): int
    {
        return $this->run()->rowCount();
    }

    /**
     * Gets the last
     *
     * @return string|null
     */
    public function getLastInsertId(): ?string
    {
        $id = $this->pdo->lastInsertId();

        return $id === '0' || ! is_string($id) ? null : $id;
    }

    /**
     * IteratorAggregate interface
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all());
    }

    /**
     * Gets the PDO object
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Converts the Query to a string
     *
     * @internal clone the builer first so it can be executed afterwards.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->queryBuilder;
    }
}
