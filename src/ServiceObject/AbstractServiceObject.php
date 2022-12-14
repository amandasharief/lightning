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

namespace Lightning\ServiceObject;

use Lightning\Params\Params;

/**
 * Service Object
 *
 * Command Pattern: "an object is used to encapsulate all information needed to perform an action or trigger an event"
 *
 * IDEA: for type hinting, create an extra method
 *
 * public function with(string $name, string $url): static
 * {
 *     $params = new Params(['name' => $name,'url' => $url]);
 *
 *     return $this->withParams($params);
 *  }
 */
abstract class AbstractServiceObject implements ServiceObjectInterface
{
    private ?Params $params;

    /**
     * A hook that is called before execute when the Service Object is run.
     */
    protected function initialize(): void
    {
    }

    /**
     * The Service Object logic that will be executed when it is run
     */
    abstract protected function execute(Params $params): Result;

    /**
     * Gets the params that will be passed when run
     */
    public function getParameters(): array
    {
        return isset($this->params) ? $this->params->toArray() : [];
    }

    /**
     * Returns a new instance with the parameters set
     */
    public function withParameters(array $parameters): static
    {
        $service = clone $this;
        $service->params = new Params($parameters);

        return $service;
    }

    /**
     * Runs the Service Object
     */
    public function run(): Result
    {
        $this->initialize();

        return $this->execute($this->params ?? new Params());
    }

    /**
     * Make this a callable
     */
    public function __invoke(): Result
    {
        return $this->run();
    }
}
