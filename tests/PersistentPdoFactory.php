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

namespace Lightning\Test;

use PDO;

/**
 * PdoFactory class
 */
class PersistentPdoFactory
{
    /**
     * Factory method
     * @param string $dsn e.g. mysql:host=127.0.0.1;port=3306;dbname=crm;charset=utf8mb
     */
    public function create(string $dsn, ?string $username, ?string $password): PDO
    {
        return new PDO($dsn, $username, $password, [

            PDO::ATTR_PERSISTENT => true,

            /**
             * 1. This must be set to false for security reasons
             * 2. It also plays a part in cast in casting data types such as integer
             */
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
}