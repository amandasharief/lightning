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

namespace Lightning\Entity;

use JsonSerializable;

/**
 * Entity Interface
 *
 * When using this make sure that you do not create calculation functions, instead the get method should do the calculation.
 *
 * @see https://martinfowler.com/bliki/AnemicDomainModel.html
 * @see https://martinfowler.com/bliki/POJO.html
 */
interface EntityInterface extends JsonSerializable
{
    /**
     * Create the Entity using data from an array
     */
    public static function fromState(array $state): self;

    /**
     * Gets the state of the entity
     */
    public function toState(): array;

    /**
     * Checks if the Entity is a new and has not been persisted
     */
    public function isNew(): bool;

    /**
     * Marks the Entity persisted state
     */
    public function markPersisted(bool $persisted): void;
}
