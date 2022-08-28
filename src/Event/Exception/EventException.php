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

namespace Lightning\Event\Exception;

use RuntimeException;

/**
 * EventException
 *
 * A marker exception which can be thrown inside Event objects
 */
class EventException extends RuntimeException
{
}
