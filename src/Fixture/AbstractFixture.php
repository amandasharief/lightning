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

use Exception;

/**
 * Undocumented class
 */
abstract class AbstractFixture
{
    protected array $records = [];
    protected string $table;

    public function __construct()
    {
        if (! isset($this->table)) {
            throw new Exception(sprintf('Fixture `%s` does not have table property', get_class($this)));
        }
        $this->initialize();
    }

    protected function initialize(): void
    {
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecords(): array
    {
        return $this->records;
    }
}
