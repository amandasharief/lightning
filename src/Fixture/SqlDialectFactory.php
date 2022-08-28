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

namespace Lightning\Fixture;

use InvalidArgumentException;
use Lightning\Fixture\SqlDialect\MysqlDialect;
use Lightning\Fixture\SqlDialect\SqliteDialect;
use Lightning\Fixture\SqlDialect\PostgresDialect;

// TODO: Move to own libraries so they can be reused
class SqlDialectFactory
{
    /**
     * TODO: use magic method instead of switch
     * @param string $driver the PDO driver name, e.g. sqlite, pgsql or mysql
     * @return SqlDialectInterface
     */
    public function create(string $driver): SqlDialectInterface
    {
        switch ($driver) {
            case 'mysql':
                $dialect = $this->createMysqlDialect();

            break;
            case 'pgsql':
                $dialect = $this->createPostgresDialect();

            break;
            case 'sqlite':
                $dialect = $this->createSqliteDialect();

            break;
            default:
                throw new InvalidArgumentException("No SQL dialect available for `{$driver}`");
        }

        return $dialect;
    }

    public function createMysqlDialect(): MysqlDialect
    {
        return new MysqlDialect();
    }

    public function createPostgresDialect(): PostgresDialect
    {
        return new PostgresDialect();
    }

    public function createSqliteDialect(): SqliteDialect
    {
        return new SqliteDialect();
    }
}
